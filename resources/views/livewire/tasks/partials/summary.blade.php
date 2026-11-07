{{-- Side summary of a task (used on the detail, confirmation and rejection pages): same look everywhere. --}}
<x-card :title="__('Summary')">
    <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
        <x-avatar :user="$task->employee->user" size="md" />
        <div class="min-w-0">
            <p class="info-label">{{ __('Assigned to') }}</p>
            <p class="truncate text-sm font-semibold text-slate-900">{{ $task->employee->user->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $task->employee->job_title }}</p>
        </div>
    </div>

    <x-info-list class="mt-5" :items="[
        __('Status') => $task->status->label(),
        __('Priority') => $task->priority->label(),
        __('Department') => $task->employee->department->name,
        __('Manager') => $task->employee->manager?->user->name ?? '—',
        __('Due date') => $task->due_date?->format('d/m/Y') ?? '—',
        __('Created') => $task->created_at->format('d/m/Y H:i'),
        __('Completed') => $task->completed_at?->format('d/m/Y H:i') ?? '—',
        __('Reviewed by') => $task->reviewer?->name ?? '—',
        __('Reviewed at') => $task->reviewed_at?->format('d/m/Y H:i') ?? '—',
    ]" />
</x-card>
