<div class="space-y-5">
    {{-- Page actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if ($scope === 'mine') {{ __('Everything assigned to you and its approval status.') }}
            @elseif ($scope === 'team') {{ __('Tasks submitted by the people you manage. Completed tasks are waiting for your decision.') }}
            @else {{ __('Every task in the company. Use the filters to narrow down.') }}
            @endif
        </p>
        @if ($scope === 'mine' || ($scope === 'all' && auth()->user()->isSuperAdmin()))
            <x-button :href="route('tasks.create')" variant="primary" icon="plus">{{ __('New task') }}</x-button>
        @endif
    </div>

    {{-- Filters --}}
    <x-filter-bar :placeholder="__('Search by task title…')" :label="__('Search title')" :total="$tasks->total()" noun="task">
        <x-searchable-select id="status" :label="__('Status')" wire:model="status" :placeholder="__('All statuses')" :all-label="__('All statuses')"
            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-searchable-select id="priority" :label="__('Priority')" wire:model="priority" :placeholder="__('All priorities')" :all-label="__('All priorities')"
            :options="collect($priorities)->mapWithKeys(fn ($p) => [$p->value => $p->label()])" />
        @if ($scope !== 'mine')
            <x-searchable-select id="employee" :label="__('Employee')" wire:model="employeeId" :placeholder="__('All employees')" :all-label="__('All employees')"
                :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->user->name])" />
        @endif
        @if ($scope === 'all')
            <x-searchable-select id="department" :label="__('Department')" wire:model="departmentId" :placeholder="__('All departments')" :all-label="__('All departments')"
                :options="$departments->pluck('name', 'id')" />
        @endif
        <x-date-input id="from" :label="__('Created from')" wire:model="dateFrom" compact />
        <x-date-input id="to" :label="__('Created to')" wire:model="dateTo" compact />
    </x-filter-bar>

    {{-- Table (desktop) / cards (mobile) --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200"
         wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($tasks->isEmpty())
            <x-empty-state :title="__('No tasks found')" :text="$search || $status || $priority || $employeeId || $departmentId || $dateFrom || $dateTo ? __('Try changing your filters or press Clear.') : __('Nothing here yet.')">
                @if ($scope === 'mine')
                    <x-button :href="route('tasks.create')" variant="primary" icon="plus">{{ __('Create your first task') }}</x-button>
                @endif
            </x-empty-state>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100/70 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Task') }}</th>
                            @if ($scope !== 'mine')<th class="px-4 py-3 text-start">{{ __('Employee') }}</th>@endif
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Priority') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Due') }}</th>
                            <th class="w-16 px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <tr wire:key="task-{{ $task->id }}" class="transition hover:bg-slate-50/70">
                                <td class="max-w-xs px-4 py-3">
                                    <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-600">{{ $task->title }}</a>
                                    <p class="text-xs text-slate-400">{{ __('Created') }} {{ $task->created_at->diffForHumans() }}</p>
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
                                <td class="px-4 py-3 text-end">
                                    @include('livewire.tasks.partials.actions-menu', ['task' => $task])
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
                            <div class="flex items-center gap-1">
                                <x-status-badge :status="$task->status" />
                                @include('livewire.tasks.partials.actions-menu', ['task' => $task])
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <x-priority-badge :priority="$task->priority" />
                            @if ($scope !== 'mine')
                                <span class="inline-flex items-center gap-1.5"><x-avatar :user="$task->employee->user" size="xs" />{{ $task->employee->user->name }}</span>
                            @endif
                            <span>{{ __('Due') }} {{ $task->due_date?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>

            <x-table-footer :paginator="$tasks" />
        @endif
    </div>
</div>
