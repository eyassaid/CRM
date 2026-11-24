<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TeamReportController extends Controller
{
    /**
     * Admin overview: every team with its client and project counts.
     */
    public function index(): JsonResponse
    {
        $teams = Team::with(['section', 'leader'])->withCount($this->counts())->paginate();

        return TeamResource::collection($teams)->response();
    }

    /**
     * One team, with how many clients each member (and the leader) brought to it, and its clients with their projects.
     */
    public function show(Team $team): JsonResponse
    {
        Gate::authorize('viewReport', $team);

        $broughtToThisTeam = ['clients' => fn ($query) => $query->where('team_id', $team->id)];

        $team->load([
            'section',
            'leader' => fn ($query) => $query->withCount($broughtToThisTeam),
            'members' => fn ($query) => $query->withCount($broughtToThisTeam)->orderByDesc('clients_count'),
            'clients' => fn ($query) => $query->orderBy('name'),
            'clients.projects' => fn ($query) => $query->orderBy('name'),
        ])->loadCount($this->counts());

        return (new TeamResource($team))->response();
    }

    /**
     * Counts read by TeamResource: clients_count, projects_count and projects_{status}_count for each status.
     *
     * @return array<int|string, mixed>
     */
    private function counts(): array
    {
        $counts = ['clients', 'projects'];

        foreach (ProjectStatus::cases() as $status) {
            $counts["projects as projects_{$status->value}_count"] =
                fn ($query) => $query->where('projects.status', $status->value);
        }

        return $counts;
    }
}
