@php
    $item = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('Departments group employees. A department that still has staff cannot be deleted.') }}</p>
        <x-button :href="route('admin.departments.create')" variant="primary" icon="plus">{{ __('New department') }}</x-button>
    </div>

    <x-filter-bar :placeholder="__('Department name or code…')" :label="__('Search departments')" :total="$departments->total()" noun="department" />

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage,perPage">
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
                                    <a href="{{ route('admin.departments.edit', $d) }}" wire:navigate role="menuitem" class="{{ $item }} hover:bg-slate-50">
                                        <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z"/></svg>
                                        {{ __('Edit') }}
                                    </a>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <a href="{{ route('admin.departments.delete', $d) }}" wire:navigate role="menuitem" class="{{ $item }} text-rose-600 hover:bg-rose-50">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"/></svg>
                                        {{ __('Delete') }}
                                    </a>
                                </x-row-menu>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-table-footer :paginator="$departments" />
        @endif
    </div>
</div>
