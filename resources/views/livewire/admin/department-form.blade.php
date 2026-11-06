<div class="mx-auto max-w-2xl space-y-5">
    <x-button :href="route('admin.departments')" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <form wire:submit="save" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-5">
            <h2 class="text-lg font-bold text-slate-900">{{ $departmentId ? __('Edit department') : __('New department') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('Fields marked * are required.') }}</p>
        </div>

        <div class="grid gap-5 px-6 py-6 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="dp-name" class="block text-sm font-medium text-slate-700">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                <input id="dp-name" type="text" wire:model="name" autofocus
                       class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('name') ring-rose-400 @else ring-slate-300 @enderror">
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="dp-code" class="block text-sm font-medium text-slate-700">{{ __('Code') }} <span class="text-rose-500">*</span></label>
                <input id="dp-code" type="text" wire:model="code" maxlength="10"
                       class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 font-mono text-sm uppercase shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('code') ring-rose-400 @else ring-slate-300 @enderror">
                @error('code')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.departments')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $departmentId ? __('Save changes') : __('Create department') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </x-button>
        </div>
    </form>
</div>
