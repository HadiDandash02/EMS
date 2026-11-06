<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TaskHistory */
class TaskHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action->value,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status->value,
            'reason' => $this->reason,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? ['id' => $this->actor->id, 'name' => $this->actor->name] : null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
