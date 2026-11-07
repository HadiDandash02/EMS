<x-page :title="__('Reject task')" :subtitle="__('The task will be sent back to the employee. A reason is required.')"
        :crumbs="[[__('Tasks'), $task->listUrlFor(auth()->user())], [$task->title, route('tasks.show', $task)], [__('Reject task'), null]]">
    <div class="grid gap-6 lg:grid-cols-3">
        <form wire:submit="reject" class="lg:col-span-2">
            <x-card :title="__('Reason for rejection')" :hint="__('Fields marked * are required.')" tone="danger">
                <label for="rj-reason" class="block text-sm font-medium text-slate-700">{{ __('Reason') }} <span class="text-rose-500">*</span></label>
                <textarea id="rj-reason" rows="8" wire:model="reason" autofocus
                          class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-rose-500 @error('reason') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                @error('reason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror

                <x-slot:foot>
                    <x-button :href="route('tasks.show', $task)" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                    <x-button type="submit" variant="danger" icon="x" wire:loading.attr="disabled" wire:target="reject">
                        <span wire:loading.remove wire:target="reject">{{ __('Reject task') }}</span>
                        <span wire:loading wire:target="reject">{{ __('Rejecting…') }}</span>
                    </x-button>
                </x-slot:foot>
            </x-card>
        </form>

        <aside>@include('livewire.tasks.partials.summary', ['task' => $task])</aside>
    </div>
</x-page>
