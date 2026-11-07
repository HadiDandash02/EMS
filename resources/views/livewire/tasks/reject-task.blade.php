<div class="mx-auto max-w-2xl space-y-5">
    <x-button :href="route('tasks.show', $task)" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <form wire:submit="reject" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-rose-100 bg-gradient-to-r from-rose-50 via-white to-white px-6 py-5">
            <h2 class="text-lg font-bold text-slate-900">{{ __('Reject task') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('The task will be sent back to the employee. A reason is required.') }}</p>
        </div>

        <div class="space-y-5 px-6 py-6">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <x-avatar :user="$task->employee->user" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                    <p class="text-xs text-slate-500">{{ $task->employee->user->name }} · {{ $task->employee->department->name }}</p>
                </div>
                <x-status-badge :status="$task->status" class="ms-auto" />
            </div>

            <div>
                <label for="rj-reason" class="block text-sm font-medium text-slate-700">{{ __('Reason') }} <span class="text-rose-500">*</span></label>
                <textarea id="rj-reason" rows="5" wire:model="reason" autofocus
                          class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-rose-500 @error('reason') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                @error('reason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <x-button :href="route('tasks.show', $task)" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="submit" variant="danger" icon="x" wire:loading.attr="disabled" wire:target="reject">
                <span wire:loading.remove wire:target="reject">{{ __('Reject task') }}</span>
                <span wire:loading wire:target="reject">{{ __('Rejecting…') }}</span>
            </x-button>
        </div>
    </form>
</div>
