{{--
    The "⋮" menu of a task row: every action the signed-in user may take, in one place.
    Every item opens its own page (edit form, confirmation, reject form) - no popups.
    UI hints only - TaskPolicy and TaskWorkflowService re-check every action on the server.
--}}
@php
    use App\Enums\TaskStatus;

    $me = auth()->user();
    $isAdmin = $me->isSuperAdmin();
    $row = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
    $ico = 'h-[18px] w-[18px] shrink-0';

    $canEdit = $me->can('update', $task) && ($task->status === TaskStatus::Pending || $isAdmin);
    $canStart = $me->can('start', $task) && in_array($task->status, [TaskStatus::Pending, TaskStatus::Rejected], true);
    $canComplete = $me->can('complete', $task) && in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true);
    // [CHANGE-REQUEST #3] (4/5) use: $canCancel = $me->can('cancel', $task) && in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true);
    $canCancel = $me->can('cancel', $task) && $task->status === TaskStatus::Pending;
    $canReview = $task->status === TaskStatus::Completed && $me->can('approve', $task);
    $canDelete = $me->can('delete', $task) && ($isAdmin || in_array($task->status, [TaskStatus::Pending, TaskStatus::Cancelled], true));
@endphp

<x-row-menu>
    <a href="{{ route('tasks.show', $task) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-slate-50">
        <svg class="{{ $ico }} text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 8.2 7.36 5 12 5s8.58 3.2 9.96 6.68a1 1 0 010 .64C20.58 15.8 16.64 19 12 19s-8.58-3.2-9.96-6.68z"/><circle cx="12" cy="12" r="3"/></svg>
        {{ __('View details') }}
    </a>

    @if ($canEdit)
        <a href="{{ route('tasks.edit', $task) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-slate-50">
            <svg class="{{ $ico }} text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z"/></svg>
            {{ __('Edit') }}
        </a>
    @endif

    @if ($canStart)
        <button type="button" role="menuitem" wire:click="startTask({{ $task->id }})" class="{{ $row }} hover:bg-blue-50 hover:text-blue-700">
            <svg class="{{ $ico }} text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.65v12.7a.75.75 0 001.14.64l10.5-6.35a.75.75 0 000-1.28L6.39 5.01a.75.75 0 00-1.14.64z"/></svg>
            {{ $task->status === TaskStatus::Rejected ? __('Rework') : __('Start') }}
        </button>
    @endif

    @if ($canComplete)
        <a href="{{ route('tasks.confirm', [$task, 'complete']) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-indigo-50 hover:text-indigo-700">
            <svg class="{{ $ico }} text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('Complete') }}
        </a>
    @endif

    @if ($canReview)
        <a href="{{ route('tasks.confirm', [$task, 'approve']) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-emerald-50 hover:text-emerald-700">
            <svg class="{{ $ico }} text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            {{ __('Approve') }}
        </a>
        @can('reject', $task)
            <a href="{{ route('tasks.reject', $task) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-rose-50 hover:text-rose-700">
                <svg class="{{ $ico }} text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('Reject') }}
            </a>
        @endcan
    @endif

    @if ($canCancel)
        <a href="{{ route('tasks.confirm', [$task, 'cancel']) }}" wire:navigate role="menuitem" class="{{ $row }} hover:bg-amber-50 hover:text-amber-700">
            <svg class="{{ $ico }} text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 5.64a9 9 0 11-12.73 0 9 9 0 0112.73 0zM5.64 5.64l12.72 12.72"/></svg>
            {{ __('Cancel task') }}
        </a>
    @endif

    @if ($canDelete)
        <div class="my-1 border-t border-slate-100"></div>
        <a href="{{ route('tasks.confirm', [$task, 'delete']) }}" wire:navigate role="menuitem" class="{{ $row }} text-rose-600 hover:bg-rose-50">
            <svg class="{{ $ico }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"/></svg>
            {{ __('Delete') }}
        </a>
    @endif
</x-row-menu>
