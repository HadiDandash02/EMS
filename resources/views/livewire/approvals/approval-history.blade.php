<div class="space-y-5">
    <p class="text-sm text-slate-500">{{ auth()->user()->canSeeAll() ? 'Every approval and rejection decision in the company.' : 'The approval and rejection decisions you have made.' }}</p>

    <x-filter-bar placeholder="Search by task title…" label="Search title" :total="$records->total()" noun="decision">
        <x-searchable-select id="decision" label="Decision" wire:model="decision" placeholder="All decisions" all-label="All decisions" :options="['approved' => 'Approved', 'rejected' => 'Rejected']" />
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($records->isEmpty())
            <x-empty-state title="No decisions found" text="Approved and rejected tasks will show up here." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Decision</th><th class="px-4 py-3">Task</th><th class="px-4 py-3">Employee</th>
                            <th class="px-4 py-3">Decided by</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">Date</th>
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
            @if ($records->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $records->links() }}</div>@endif
        @endif
    </div>
</div>
