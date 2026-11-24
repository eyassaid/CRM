<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            // The notification class, e.g. "ProjectCompleted".
            'type' => class_basename($this->type),
            // What the notification's toArray() stored (see App\Notifications). Not named `data`: a resource with a
            // `data` key is sent without the { "data": ... } wrapper.
            'details' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
