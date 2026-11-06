<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ auth()->user()->canSeeAll() ? 'Every approval and rejection decision in the company.' : 'The approval and rejection decisions you have made.' }}</p>
        <select wire:model.live="decision" aria-label="Decision" class="rounded-lg border-0 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
            <option value="">All decisions</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60">
        @if ($records->isEmpty())
            <x-empty-state title="No decisions yet" text="Approved and rejected tasks will show up here." />
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
                            <tr wire:key="rec-{{ $r->id }}">
                                <td class="px-4 py-3"><x-status-badge :status="$r->to_status" /></td>
                                <td class="max-w-xs px-4 py-3"><a href="{{ route('tasks.show', $r->task_id) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-600">{{ $r->task->title }}</a></td>
                                <td class="px-4 py-3 text-slate-600">{{ $r->task->employee->user->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $r->actor?->name ?? '—' }}</td>
                                <td class="max-w-xs px-4 py-3 text-slate-600">{{ $r->reason ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r->created_at->format('M j, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $records->links() }}</div>@endif
        @endif
    </div>
</div>
