@php
    $item = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
    $columns = ['code' => __('Code'), 'employees' => __('Employees')];
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('Departments group employees. A department that still has staff cannot be deleted.') }}</p>
        <x-button :href="route('admin.departments.create')" variant="primary" icon="plus">{{ __('New department') }}</x-button>
    </div>

    <x-filter-bar hide-search :total="$departments->total()" noun="department" :active="collect([$departmentId])->filter()->count()">
        <x-searchable-select id="dp-pick" :label="__('Department')" wire:model.live="departmentId" :placeholder="__('All departments')" :all-label="__('All departments')" :options="$departmentOptions" />
    </x-filter-bar>

    <x-data-table id="departments" :columns="$columns">
        @if ($departments->isEmpty())
            <x-empty-state :title="__('No departments found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-sort-th field="name" :label="__('Department')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="code" col="code" :label="__('Code')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="employees" col="employees" :label="__('Employees')" :sort="$sortField" :dir="$sortDirection" />
                            <x-th class="w-20 text-end last:rounded-se-lg">{{ __('Actions') }}</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($departments as $d)
                            <tr wire:key="dept-{{ $d->id }}" class="group transition-colors odd:bg-white even:bg-slate-50/50 hover:bg-indigo-50/60">
                                <td class="border-s-4 border-transparent px-4 py-3 text-xs font-medium text-slate-400 transition group-hover:border-indigo-500">{{ $departments->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $d->name }}</td>
                                <td data-col="code" class="px-4 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $d->code }}</span></td>
                                <td data-col="employees" class="px-4 py-3 text-slate-600">{{ $d->employees_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
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
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$departments" />
        @endif
    </x-data-table>
</div>
