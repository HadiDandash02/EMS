{{-- Inline action buttons (task detail page). UI hints only: TaskPolicy / TaskWorkflowService re-check everything. --}}
@php
    use App\Enums\TaskStatus;
    $btn = 'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50';
@endphp

<div class="flex flex-wrap items-center gap-1.5">
    @can('update', $task)
        @if ($task->status === TaskStatus::Pending || auth()->user()->isSuperAdmin())
            <button type="button" x-on:click="Livewire.dispatch('open-task-form', { taskId: {{ $task->id }} })"
                    class="{{ $btn }} bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:ring-slate-400">{{ __('Edit') }}</button>
        @endif
    @endcan

    @can('start', $task)
        @if (in_array($task->status, [TaskStatus::Pending, TaskStatus::Rejected], true))
            <button type="button" wire:click="startTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="startTask({{ $task->id }})"
                    class="{{ $btn }} bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200 hover:bg-blue-100 focus:ring-blue-400">
                {{ $task->status === TaskStatus::Rejected ? __('Rework') : __('Start') }}
            </button>
        @endif
    @endcan

    @can('complete', $task)
        @if (in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true))
            <button type="button" wire:click="completeTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="completeTask({{ $task->id }})"
                    wire:confirm="{{ __('Mark this task as completed and send it for approval?') }}"
                    class="{{ $btn }} bg-indigo-600 text-white hover:bg-indigo-500 focus:ring-indigo-500">{{ __('Complete') }}</button>
        @endif
    @endcan

    @can('cancel', $task)
        @if ($task->status === TaskStatus::Pending)
            <button type="button" wire:click="cancelTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="cancelTask({{ $task->id }})"
                    wire:confirm="{{ __('Cancel this task? This cannot be undone.') }}"
                    class="{{ $btn }} bg-white text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 focus:ring-rose-400">{{ __('Cancel task') }}</button>
        @endif
    @endcan

    @if ($task->status === TaskStatus::Completed)
        @can('approve', $task)
            <button type="button" wire:click="approveTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="approveTask({{ $task->id }})"
                    wire:confirm="{{ __('Approve this task?') }}"
                    class="{{ $btn }} bg-emerald-600 text-white hover:bg-emerald-500 focus:ring-emerald-500">{{ __('Approve') }}</button>
        @endcan
        @can('reject', $task)
            <button type="button" x-on:click="Livewire.dispatch('open-reject-modal', { taskId: {{ $task->id }} })"
                    class="{{ $btn }} bg-white text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 focus:ring-rose-400">{{ __('Reject') }}</button>
        @endcan
    @endif

    @can('delete', $task)
        @if (auth()->user()->isSuperAdmin() || in_array($task->status, [TaskStatus::Pending, TaskStatus::Cancelled], true))
            <button type="button" wire:click="deleteTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="deleteTask({{ $task->id }})"
                    wire:confirm="{{ __('Delete this task permanently? Its history will be deleted too.') }}"
                    class="{{ $btn }} bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-500">{{ __('Delete') }}</button>
        @endif
    @endcan
</div>
