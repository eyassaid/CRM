<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            // Teams the user is a member of, each with its section.
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
            // Clients brought by the user (scoped to one team in the team report).
            'clients_count' => $this->whenCounted('clients'),
        ];
    }
}
