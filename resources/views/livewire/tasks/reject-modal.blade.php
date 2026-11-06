<div>
    <x-modal :show="$open" :title="__('Reject task')" max-width="max-w-md">
        <form wire:submit="reject">
            <div class="px-6 pt-4">
                <p class="text-sm text-slate-500">“{{ $taskTitle }}” — {{ __('will be sent back to the employee. A reason is required.') }}</p>
            </div>
            <div class="px-6 py-4">
                <label for="rj-reason" class="block text-sm font-medium text-slate-700">{{ __('Reason') }} <span class="text-rose-500">*</span></label>
                <textarea id="rj-reason" rows="4" wire:model="reason" autofocus
                          class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-rose-500 @error('reason') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                @error('reason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="reject"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-60">
                    <span wire:loading.remove wire:target="reject">{{ __('Reject task') }}</span>
                    <span wire:loading wire:target="reject">{{ __('Rejecting…') }}</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
