<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssigned;
use App\Notifications\ProjectCompleted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// Access to a project: see ProjectPolicy. Projects a member creates for their client are requests the team leader
// approves. Marking a project done creates its invoice and notifies the admins and the team's leader. People added
// to a project's helpers are emailed.
class ProjectController extends Controller
{
    protected $folder = 'projects/';

    public function index(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $projects = $client->projects()->visibleTo($request->user())->with('assignees')->paginate();

        return ProjectResource::collection($projects)->response();
    }

    public function store(Client $client, ProjectRequest $request): JsonResponse
    {
        // The client's owner, the team leader, or an admin.
        Gate::authorize('update', $client);

        if ($request->hasAny(['user_ids', 'approved'])) {
            Gate::authorize('assignProjects', $client);
        }

        // Admins and the team leader create approved projects; the client's owner requests one.
        $manages = $request->user()->can('assignProjects', $client);

        $created_project = DB::transaction(function () use ($client, $request, $manages) {
            $project = $client->projects()->create([
                ...$request->safe()->except(['image', 'user_ids', 'approved']),
                'approved_at' => $manages ? now() : null,
            ]);
            $project->assignees()->sync($request->validated('user_ids') ?? []);

            return $project;
        });

        if ($request->hasFile('image')) {
            $path = $this->folder.$created_project->id.'/images/';
            $created_project->update(['image' => $request->file('image')->store($path, 'public')]);
        }

        $this->notifyAssigned($created_project, $request->validated('user_ids') ?? [], $request->user());

        return (new ProjectResource($created_project->load(['client', 'assignees'])))->response()->setStatusCode(201);
    }

    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return (new ProjectResource($project->load(['client', 'assignees'])))->response();
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        if ($request->hasAny(['user_ids', 'approved'])) {
            Gate::authorize('assignProjects', $project->client);
        }

        $data = $request->safe()->except(['user_ids', 'approved', 'items']);

        if ($request->validated('approved') && ! $project->isApproved()) {
            $data['approved_at'] = now();
        }

        // Marking a project done produces its invoice, and pending projects get no invoices.
        $completes = $request->completesProject();
        abort_if($completes && ! isset($data['approved_at']) && ! $project->isApproved(), 409,
            'The project is waiting for the team leader\'s approval.');

        if ($request->hasFile('image')) {
            if ($project->image && Storage::disk('public')->exists($project->image)) {
                Storage::disk('public')->delete($project->image);
            }
            $path = $this->folder.$project->id.'/images/';
            $data['image'] = $request->file('image')->store($path, 'public');
        }

        $attached = [];
        $invoice = DB::transaction(function () use ($project, $data, $request, $completes, &$attached) {
            $project->update($data);

            // People are replaced only when the request sends the list.
            if ($request->has('user_ids')) {
                $attached = $project->assignees()->sync($request->validated('user_ids') ?? [])['attached'];
            }

            // The invoice is stored as its items; the PDF is rendered on download.
            return $completes ? $project->invoices()->create([
                'items' => $request->validated('items'),
                'created_by' => $request->user()->id,
            ]) : null;
        });

        $this->notifyAssigned($project, $attached, $request->user());

        if ($invoice) {
            Notification::send(
                $this->completionRecipients($project, $request->user()),
                new ProjectCompleted($project, $invoice, $request->user()),
            );
        }

        return (new ProjectResource($project->refresh()->load(['client', 'assignees'])))->response();
    }

    /**
     * Emails the people just added to the project, except the person who added them.
     *
     * @param  array<int, int>  $userIds
     */
    private function notifyAssigned(Project $project, array $userIds, User $assignedBy): void
    {
        $users = User::whereKey($userIds)->whereKeyNot($assignedBy->id)->get();

        Notification::send($users, new ProjectAssigned($project->loadMissing('client'), $assignedBy));
    }

    /**
     * Every admin and the leader of the client's team, except the person who completed the project.
     *
     * @return Collection<int, User>
     */
    private function completionRecipients(Project $project, User $completedBy): Collection
    {
        return User::where('role', UserRole::Admin)->get()
            ->push($project->client->team?->leader)
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->is($completedBy))
            ->values();
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
