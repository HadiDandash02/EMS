<div class="mx-auto max-w-3xl space-y-5">
    <x-button :href="$cancelUrl" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <form wire:submit="save" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-5">
            <h2 class="text-lg font-bold text-slate-900">{{ $taskId ? __('Edit task') : __('New task') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('Fields marked * are required.') }}</p>
        </div>

        <div class="space-y-5 px-6 py-6">
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
                       class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('title') ring-rose-400 @else ring-slate-300 @enderror">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tf-desc" class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                <textarea id="tf-desc" rows="5" wire:model="description"
                          class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('description') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
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

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <x-button :href="$cancelUrl" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $taskId ? __('Save changes') : __('Create task') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </x-button>
        </div>
    </form>
</div>
