<div class="space-y-5">
    <p class="text-sm text-slate-500">{{ auth()->user()->canSeeAll() ? __('Every approval and rejection decision in the company.') : __('The approval and rejection decisions you have made.') }}</p>

    <x-filter-bar :placeholder="__('Search by task title…')" :label="__('Search title')" :total="$records->total()" noun="decision">
        <x-searchable-select id="decision" :label="__('Decision')" wire:model="decision" :placeholder="__('All decisions')" :all-label="__('All decisions')"
            :options="['approved' => __('Approved'), 'rejected' => __('Rejected')]" />
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($records->isEmpty())
            <x-empty-state :title="__('No decisions found')" :text="__('Approved and rejected tasks will show up here.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100/70 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Decision') }}</th><th class="px-4 py-3 text-start">{{ __('Task') }}</th><th class="px-4 py-3 text-start">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Decided by') }}</th><th class="px-4 py-3 text-start">{{ __('Reason') }}</th><th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $r)
                            <tr wire:key="rec-{{ $r->id }}" class="hover:bg-slate-50/70">
                                <td class="px-4 py-3"><x-status-badge :status="$r->to_status" /></td>
                                <td class="max-w-xs px-4 py-3"><a href="{{ route('tasks.show', $r->task_id) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-600">{{ $r->task->title }}</a></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 text-slate-700"><x-avatar :user="$r->task->employee->user" size="xs" />{{ $r->task->employee->user->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $r->actor?->name ?? '—' }}</td>
                                <td class="max-w-xs px-4 py-3 text-slate-600">{{ $r->reason ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$records" />
        @endif
    </div>
</div>
