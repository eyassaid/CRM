<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
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
            'status' => $this->status,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            // Null while a project a member requested waits for the team leader's approval.
            'approved_at' => $this->approved_at,
            'client' => new ClientResource($this->whenLoaded('client')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
        ];
    }
}
