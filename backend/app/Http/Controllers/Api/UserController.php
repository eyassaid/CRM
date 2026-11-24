<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(UserIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $user = $request->user();

        $users = User::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereHas(
                'teams',
                fn ($query) => $query->where('leader_id', $user->id),
            ))
            ->when($request->validated('search'), fn ($query, string $search) => $query->where(
                fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"),
            ))
            ->with('teams.section')
            ->paginate();

        return UserResource::collection($users)->response();
    }

    /**
     * Role is employee unless an admin chooses team leader. With a team_id the user joins that team.
     */
    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = DB::transaction(function () use ($request) {
            $team = Team::find($request->validated('team_id'));

            $user = new User($request->safe()->except(['team_id', 'role']));
            $user->role = $request->validated('role', UserRole::Employee->value);
            $user->save();

            $team?->members()->attach($user);

            return $user;
        });

        return (new UserResource($user->load('teams.section')))->response()->setStatusCode(201);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return (new UserResource($user->load('teams.section')))->response();
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        // role is not mass assignable; UserRequest only accepts it from admins.
        $user->fill($request->safe()->except('role'));
        if ($request->has('role')) {
            $user->role = $request->validated('role');
        }
        $user->save();

        return (new UserResource($user->refresh()->load('teams.section')))->response();
    }

    public function destroy(User $user): Response
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
