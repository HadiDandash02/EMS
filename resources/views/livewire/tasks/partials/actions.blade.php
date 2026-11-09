{{-- Action buttons (task detail page). Each one opens its own page - no popups. UI hints only: TaskPolicy / TaskWorkflowService re-check everything. --}}
@php
    use App\Enums\TaskStatus;
    $me = auth()->user();
@endphp

<div class="flex flex-wrap items-center gap-2">
    @can('update', $task)
        @if ($task->status === TaskStatus::Pending || $me->isSuperAdmin())
            <x-button :href="route('tasks.edit', $task)" variant="secondary" size="sm" icon="pencil">{{ __('Edit') }}</x-button>
        @endif
    @endcan

    @can('start', $task)
        @if (in_array($task->status, [TaskStatus::Pending, TaskStatus::Rejected], true))
            <x-button type="button" variant="soft" size="sm" icon="play" wire:click="startTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="startTask({{ $task->id }})">
                {{ $task->status === TaskStatus::Rejected ? __('Rework') : __('Start') }}
            </x-button>
        @endif
    @endcan

    @can('complete', $task)
        @if (in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true))
            <x-button :href="route('tasks.confirm', [$task, 'complete'])" variant="primary" size="sm" icon="check">{{ __('Complete') }}</x-button>
        @endif
    @endcan

    @if ($task->status === TaskStatus::Completed)
        @can('approve', $task)
            <x-button :href="route('tasks.confirm', [$task, 'approve'])" variant="success" size="sm" icon="check">{{ __('Approve') }}</x-button>
        @endcan
        @can('reject', $task)
            <x-button :href="route('tasks.reject', $task)" variant="soft-danger" size="sm" icon="x">{{ __('Reject') }}</x-button>
        @endcan
    @endif

    @can('cancel', $task)
        {{-- [CHANGE-REQUEST #3] (3/5) use: @if (in_array($task->status, [TaskStatus::Pending, TaskStatus::InProgress], true)) --}}
        @if ($task->status === TaskStatus::Pending)
            <x-button :href="route('tasks.confirm', [$task, 'cancel'])" variant="warning" size="sm" icon="ban">{{ __('Cancel task') }}</x-button>
        @endif
    @endcan

    @can('delete', $task)
        @if ($me->isSuperAdmin() || in_array($task->status, [TaskStatus::Pending, TaskStatus::Cancelled], true))
            <x-button :href="route('tasks.confirm', [$task, 'delete'])" variant="danger" size="sm" icon="trash">{{ __('Delete') }}</x-button>
        @endif
    @endcan
</div>
