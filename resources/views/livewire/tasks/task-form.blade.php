<div>
    <x-modal :show="$open" :title="$taskId ? __('Edit task') : __('New task')" :subtitle="__('Fields marked * are required.')">
        <form wire:submit="save">
            <div class="space-y-4 px-6 py-5">
                @if ($isAdmin && ! $taskId)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Employee') }} <span class="text-rose-500">*</span></label>
                        <x-searchable-select id="tf-owner" wire:model="employee_id" :placeholder="__('Select an employee…')" :options="$owners" :error="$errors->has('employee_id')" />
                        @error('employee_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div>
                    <label for="tf-title" class="block text-sm font-medium text-slate-700">{{ __('Title') }} <span class="text-rose-500">*</span></label>
                    <input id="tf-title" type="text" wire:model="title" maxlength="150" autofocus
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('title') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tf-desc" class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                    <textarea id="tf-desc" rows="4" wire:model="description"
                              class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('description') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                    @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Priority') }} <span class="text-rose-500">*</span></label>
                        <x-searchable-select id="tf-priority" wire:model="priority" :options="collect($priorities)->mapWithKeys(fn ($p) => [$p->value => $p->label()])" :error="$errors->has('priority')" />
                        @error('priority')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-date-input id="tf-due" :label="__('Due date')" wire:model="due_date" :min="now()->toDateString()" :error="$errors->has('due_date')" />
                        @error('due_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
                    {{ $taskId ? __('Save changes') : __('Create task') }}
                </button>
            </div>
        </form>
    </x-modal>
</div>
