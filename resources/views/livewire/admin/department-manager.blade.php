@php
    $item = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('Departments group employees. A department that still has staff cannot be deleted.') }}</p>
        <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            {{ __('New department') }}
        </button>
    </div>

    <x-filter-bar :placeholder="__('Department name or code…')" :label="__('Search departments')" :total="$departments->total()" noun="department" />

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($departments->isEmpty())
            <x-empty-state :title="__('No departments found')" :text="__('Try a different search or press Clear.')" />
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100/70 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr><th class="px-4 py-3 text-start">{{ __('Department') }}</th><th class="px-4 py-3 text-start">{{ __('Code') }}</th><th class="px-4 py-3 text-start">{{ __('Employees') }}</th><th class="w-16 px-4 py-3 text-end">{{ __('Actions') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($departments as $d)
                        <tr wire:key="dept-{{ $d->id }}" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $d->name }}</td>
                            <td class="px-4 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $d->code }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $d->employees_count }}</td>
                            <td class="px-4 py-3 text-end">
                                <x-row-menu>
                                    <button type="button" role="menuitem" wire:click="edit({{ $d->id }})" class="{{ $item }} hover:bg-slate-50">
                                        <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z"/></svg>
                                        {{ __('Edit') }}
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <button type="button" role="menuitem" wire:click="confirmDelete({{ $d->id }})" class="{{ $item }} text-rose-600 hover:bg-rose-50">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"/></svg>
                                        {{ __('Delete') }}
                                    </button>
                                </x-row-menu>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($departments->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $departments->links() }}</div>@endif
        @endif
    </div>

    <x-modal :show="$formOpen" :title="$departmentId ? __('Edit department') : __('New department')" max-width="max-w-md">
        <form wire:submit="save">
            <div class="space-y-4 p-6">
                <div>
                    <label for="dp-name" class="block text-sm font-medium text-slate-700">{{ __('Name') }} *</label>
                    <input id="dp-name" type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('name') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dp-code" class="block text-sm font-medium text-slate-700">{{ __('Code') }} *</label>
                    <input id="dp-code" type="text" wire:model="code" maxlength="10" class="mt-1 block w-full rounded-lg border-0 px-3 py-2 font-mono text-sm uppercase ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('code') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('code')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">{{ $departmentId ? __('Save changes') : __('Create department') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$deleteId !== null" :title="__('Delete department')" close="cancelDelete" max-width="max-w-md">
        <p class="px-6 py-5 text-sm text-slate-600">{{ __('Permanently delete') }} <strong class="text-slate-900">{{ $deleteName }}</strong>? {{ __('This cannot be undone.') }}</p>
        <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
            <button type="button" wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
            <button type="button" wire:click="delete" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">{{ __('Delete') }}</button>
        </div>
    </x-modal>
</div>
