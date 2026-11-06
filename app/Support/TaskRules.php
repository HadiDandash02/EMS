<?php

namespace App\Support;

use App\Enums\TaskPriority;
use Illuminate\Validation\Rule;

/**
 * One definition of "what is a valid task payload", shared by the Livewire form and
 * the API Form Requests, so the two channels can never drift apart.
 */
class TaskRules
{
    /** @return array<string, array<int, mixed>> */
    public static function fields(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
