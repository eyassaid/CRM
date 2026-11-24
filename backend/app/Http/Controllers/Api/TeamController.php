<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Section $section): JsonResponse
    {
        $teams = $section->teams()->with(['leader', 'section', 'members'])->paginate();

        return TeamResource::collection($teams)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TeamRequest $request): JsonResponse
    {
        $team = DB::transaction(function () use ($request) {
            $team = Team::create($request->safe()->except('members_ids'));
            $team->members()->sync($request->validated('members_ids') ?? []);

            return $team;
        });

        return (new TeamResource($team->load(['leader', 'section', 'members'])))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team): JsonResponse
    {
        Gate::authorize('view', $team);

        return (new TeamResource($team->load(['leader', 'section', 'members'])))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TeamRequest $request, Team $team): JsonResponse
    {
        DB::transaction(function () use ($request, $team) {
            $team->update($request->safe()->except('members_ids'));

            // Members are replaced only when the request sends the list.
            if ($request->has('members_ids')) {
                $team->members()->sync($request->validated('members_ids') ?? []);
            }

            // People who left the team stop working on its projects.
            $team->unassignFormerMembers();
        });

        return (new TeamResource($team->refresh()->load(['leader', 'section', 'members'])))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team): Response
    {
        abort_if($team->members()->exists(), 409, 'Remove the team\'s members before deleting it.');

        DB::transaction(function () use ($team) {
            // Its clients stay (without a team), but nobody keeps working on their projects through this team.
            DB::table('project_user')->whereIn('project_id', $team->projects()->pluck('projects.id'))->delete();
            $team->delete();
        });

        return response()->noContent();
    }
}
