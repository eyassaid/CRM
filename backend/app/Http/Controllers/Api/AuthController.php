<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $user->createToken('api-token')->accessToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }

    /**
     * Teams the current user can assign clients to: their own teams (member or leader), or every team for admins.
     */
    public function teams(Request $request): JsonResponse
    {
        $user = $request->user();

        $teams = Team::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereIn('id', $user->teamIds()))
            ->orderBy('name')
            ->get();

        return TeamResource::collection($teams)->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()?->revoke();

        return response()->json(['message' => 'Successfully logged out.']);
    }
}
