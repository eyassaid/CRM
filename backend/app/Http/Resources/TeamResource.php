<?php

namespace App\Http\Resources;

use App\Enums\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'leader_id' => $this->leader_id,
            'leader_name' => $this->whenLoaded('leader', fn () => $this->leader->name),
            'section_id' => $this->section_id,
            'section_name' => $this->whenLoaded('section', fn () => $this->section->name),
            'leader' => new UserResource($this->whenLoaded('leader')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'clients' => ClientResource::collection($this->whenLoaded('clients')),
            'clients_count' => $this->whenCounted('clients'),
            'projects_count' => $this->whenCounted('projects'),
            // Present when the controller counted projects per status (see TeamReportController).
            'projects_by_status' => $this->when(
                array_key_exists('projects_'.ProjectStatus::Done->value.'_count', $this->resource->getAttributes()),
                fn () => collect(ProjectStatus::cases())->mapWithKeys(
                    fn (ProjectStatus $status) => [$status->value => $this->{"projects_{$status->value}_count"}],
                ),
            ),
        ];
    }
}
