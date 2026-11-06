<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center" role="dialog" aria-modal="true"
             x-data x-on:keydown.escape.window="$wire.close()">
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl" x-on:click.outside="$wire.close()">
                <form wire:submit="reject">
                    <div class="px-6 pt-5">
                        <h2 class="text-base font-semibold text-slate-900">Reject task</h2>
                        <p class="mt-1 text-sm text-slate-500">“{{ $taskTitle }}” will be sent back to the employee. A reason is required.</p>
                    </div>
                    <div class="px-6 py-4">
                        <label for="rj-reason" class="block text-sm font-medium text-slate-700">Reason <span class="text-rose-500">*</span></label>
                        <textarea id="rj-reason" rows="4" wire:model="reason" autofocus
                                  class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-rose-500 @error('reason') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                        @error('reason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-3 rounded-b-xl bg-slate-50 px-6 py-4">
                        <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="reject"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-60">
                            <span wire:loading.remove wire:target="reject">Reject task</span>
                            <span wire:loading wire:target="reject">Rejecting…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
