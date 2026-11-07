@php
    $columns = ['employee' => __('Employee'), 'by' => __('Decided by'), 'reason' => __('Reason'), 'date' => __('Date')];
@endphp
<div class="space-y-5">
    <p class="text-sm text-slate-500">{{ auth()->user()->canSeeAll() ? __('Every approval and rejection decision in the company.') : __('The approval and rejection decisions you have made.') }}</p>

    <x-filter-bar :placeholder="__('Search by task title…')" :label="__('Search title')" :total="$records->total()" noun="decision" :active="collect([$decision])->filter()->count()">
        <x-searchable-select id="decision" :label="__('Decision')" wire:model.live="decision" :placeholder="__('All decisions')" :all-label="__('All decisions')"
            :options="['approved' => __('Approved'), 'rejected' => __('Rejected')]" />
    </x-filter-bar>

    <x-data-table id="approvals" :columns="$columns">
        @if ($records->isEmpty())
            <x-empty-state :title="__('No decisions found')" :text="__('Approved and rejected tasks will show up here.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-sort-th field="decision" :label="__('Decision')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="task" :label="__('Task')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="employee" col="employee" :label="__('Employee')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="by" col="by" :label="__('Decided by')" :sort="$sortField" :dir="$sortDirection" />
                            <x-th col="reason">{{ __('Reason') }}</x-th>
                            <x-sort-th field="date" col="date" :label="__('Date')" :sort="$sortField" :dir="$sortDirection" class="last:rounded-se-lg" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $r)
                            <tr wire:key="rec-{{ $r->id }}" class="group transition-colors odd:bg-white even:bg-slate-50/50 hover:bg-indigo-50/60">
                                <td class="border-s-4 border-transparent px-4 py-3 text-xs font-medium text-slate-400 transition group-hover:border-indigo-500">{{ $records->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$r->to_status" /></td>
                                <td class="max-w-xs px-4 py-3"><a href="{{ route('tasks.show', $r->task_id) }}" wire:navigate class="font-semibold text-slate-900 transition hover:text-indigo-600">{{ $r->task->title }}</a></td>
                                <td data-col="employee" class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 text-slate-700"><x-avatar :user="$r->task->employee->user" size="xs" />{{ $r->task->employee->user->name }}</span>
                                </td>
                                <td data-col="by" class="px-4 py-3 text-slate-600">{{ $r->actor?->name ?? '—' }}</td>
                                <td data-col="reason" class="max-w-xs px-4 py-3 text-slate-600">{{ $r->reason ?: '—' }}</td>
                                <td data-col="date" class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$records" />
        @endif
    </x-data-table>
</div>
