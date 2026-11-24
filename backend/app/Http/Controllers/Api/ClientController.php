<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\ClientIndexRequest;
use App\Http\Requests\Clients\ClientStoreRequest;
use App\Http\Requests\Clients\ClientUpdateRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    public function index(ClientIndexRequest $request): JsonResponse
    {
        $user = $request->user();

        $clients = Client::query()
            ->visibleTo($user)
            ->when($request->validated('search'), fn ($query, string $search) => $query->where(
                fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('phone_number', "%{$search}%"),
            ))
            ->with('team')
            ->withCount(['projects' => fn ($query) => $query->visibleTo($user)])
            ->paginate();

        return ClientResource::collection($clients)->response();
    }

    public function store(ClientStoreRequest $request): JsonResponse
    {
        $created_client = Client::create([
            ...$request->safe()->except('image'),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('image')) {
            $path = "clients/$created_client->id";
            $created_client->image = $request->file('image')->store($path, 'public');
            $created_client->save();
        }

        return (new ClientResource($created_client->load('team')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $client->load(['team', 'projects' => fn ($query) => $query->visibleTo($request->user())]);

        return (new ClientResource($client))->response();
    }

    public function update(ClientUpdateRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $data = $request->validated();
        if ($request->hasFile('image')) {
            // check for old image:
            if ($client->image && Storage::disk('public')->exists($client->image)) {
                Storage::disk('public')->delete($client->image);
            }
            $path = "clients/$client->id";
            $data['image'] = $request->file('image')->store($path, 'public');
        }
        $client->update($data);

        // Moving to another team takes people outside that team off the client's projects.
        if ($client->wasChanged('team_id')) {
            $client->team?->unassignFormerMembers();
        }

        return (new ClientResource($client->refresh()->load('team')))->response();
    }

    public function destroy(Client $client): Response
    {
        Gate::authorize('delete', $client);

        $client->delete();

        return response()->noContent();
    }
}
