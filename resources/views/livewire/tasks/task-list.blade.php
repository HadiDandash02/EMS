<div class="space-y-5">
    {{-- Page actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if ($scope === 'mine') Everything assigned to you and its approval status.
            @elseif ($scope === 'team') Tasks submitted by the people you manage. Completed tasks are waiting for your decision.
            @else Every task in the company. Use the filters to narrow down.
            @endif
        </p>
        @if ($scope === 'mine' || ($scope === 'all' && auth()->user()->isSuperAdmin()))
            <button type="button" x-on:click="Livewire.dispatch('open-task-form')"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                New task
            </button>
        @endif
    </div>

    {{-- Filters --}}
    <x-filter-bar placeholder="Search by task title…" label="Search title" :total="$tasks->total()" noun="task">
        <x-searchable-select id="status" label="Status" wire:model="status" placeholder="All statuses" all-label="All statuses"
            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-searchable-select id="priority" label="Priority" wire:model="priority" placeholder="All priorities" all-label="All priorities"
            :options="collect($priorities)->mapWithKeys(fn ($p) => [$p->value => $p->label()])" />
        @if ($scope !== 'mine')
            <x-searchable-select id="employee" label="Employee" wire:model="employeeId" placeholder="All employees" all-label="All employees"
                :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->user->name])" />
        @endif
        @if ($scope === 'all')
            <x-searchable-select id="department" label="Department" wire:model="departmentId" placeholder="All departments" all-label="All departments"
                :options="$departments->pluck('name', 'id')" />
        @endif
        <x-date-input id="from" label="Created from" wire:model="dateFrom" compact />
        <x-date-input id="to" label="Created to" wire:model="dateTo" compact />
    </x-filter-bar>

    {{-- Table (desktop) / cards (mobile) --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200"
         wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($tasks->isEmpty())
            <x-empty-state title="No tasks found" :text="$search || $status || $priority || $employeeId || $departmentId || $dateFrom || $dateTo ? 'Try changing your filters or press Clear.' : 'Nothing here yet.'">
                @if ($scope === 'mine')
                    <button type="button" x-on:click="Livewire.dispatch('open-task-form')" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create your first task</button>
                @endif
            </x-empty-state>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Task</th>
                            @if ($scope !== 'mine')<th class="px-4 py-3">Employee</th>@endif
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Due</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <tr wire:key="task-{{ $task->id }}" class="transition hover:bg-slate-50/70">
                                <td class="max-w-xs px-4 py-3">
                                    <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-600">{{ $task->title }}</a>
                                    <p class="text-xs text-slate-400">Created {{ $task->created_at->diffForHumans() }}</p>
                                </td>
                                @if ($scope !== 'mine')
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :user="$task->employee->user" size="sm" />
                                            <div>
                                                <p class="text-slate-800">{{ $task->employee->user->name }}</p>
                                                <p class="text-xs text-slate-400">{{ $task->employee->department->name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-4 py-3"><x-status-badge :status="$task->status" /></td>
                                <td class="px-4 py-3"><x-priority-badge :priority="$task->priority" /></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @include('livewire.tasks.partials.actions', ['task' => $task])
                                        <a href="{{ route('tasks.show', $task) }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-500">View</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($tasks as $task)
                    <li wire:key="card-{{ $task->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-medium text-slate-900">{{ $task->title }}</a>
                            <x-status-badge :status="$task->status" />
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <x-priority-badge :priority="$task->priority" />
                            @if ($scope !== 'mine')
                                <span class="inline-flex items-center gap-1.5"><x-avatar :user="$task->employee->user" size="xs" />{{ $task->employee->user->name }}</span>
                            @endif
                            <span>Due {{ $task->due_date?->format('d/m') ?? '—' }}</span>
                        </div>
                        @include('livewire.tasks.partials.actions', ['task' => $task])
                    </li>
                @endforeach
            </ul>

            @if ($tasks->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $tasks->links() }}</div>
            @endif
        @endif
    </div>
</div>
