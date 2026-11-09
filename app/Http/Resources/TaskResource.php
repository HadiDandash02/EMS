<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Explicit whitelist of what the API exposes. Notably NO emails, password hashes or
 * internal columns - the resource is the contract, not the model.
 *
 * @mixin \App\Models\Task
 */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => ['value' => $this->priority->value, 'label' => $this->priority->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'due_date' => $this->due_date?->toDateString(),
            // [CHANGE-REQUEST #4: new API field "is_overdue"] UNCOMMENT (test idea: tests/Feature/Api/TaskApiTest.php -> assertJsonPath('data.is_overdue', true) for a task with a past due date)
            // 'is_overdue' => $this->due_date !== null && $this->due_date->isPast()
            //     && in_array($this->status, [TaskStatus::Pending, TaskStatus::InProgress, TaskStatus::Rejected], true),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->user->name,
                'department' => $this->employee->department?->name,
            ]),

            'review' => $this->when($this->reviewed_at !== null, fn () => [
                'reviewed_by' => $this->reviewer?->name,
                'reviewed_at' => $this->reviewed_at->toIso8601String(),
                'rejection_reason' => $this->rejection_reason,
            ]),

            'history' => TaskHistoryResource::collection($this->whenLoaded('histories')),

            // Lets API clients render the right buttons; the server still enforces everything.
            'abilities' => [
                'update' => $user->can('update', $this->resource) && $this->status === TaskStatus::Pending,
                'approve' => $user->can('approve', $this->resource) && $this->status === TaskStatus::Completed,
            ],
        ];
    }
}
