@php
    $columns = array_filter([
        'employee' => $scope !== 'mine' ? __('Employee') : null,
        'status' => __('Status'),
        'priority' => __('Priority'),
        'due' => __('Due'),
        'created' => __('Created'),
    ]);
@endphp
<div class="space-y-5">
    {{-- Page actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if ($scope === 'mine') {{ __('Everything assigned to you and its approval status.') }}
            @elseif ($scope === 'team') {{ __('Tasks submitted by the people you manage. Completed tasks are waiting for your decision.') }}
            @elseif (auth()->user()->isSuperAdmin()) {{ __('Every task in the company. Use the filters to narrow down.') }}
            @else {{ __('Every task of your department. Use the filters to narrow down.') }}
            @endif
        </p>
        @if ($scope === 'mine' || ($scope === 'all' && auth()->user()->isSuperAdmin()))
            <x-button :href="route('tasks.create')" variant="primary" icon="plus">{{ __('New task') }}</x-button>
        @endif
    </div>

    {{-- Filters --}}
    <x-filter-bar hide-search :total="$tasks->total()" noun="task" :active="collect([$taskId, $status, $priority, $employeeId, $departmentId, $dateFrom, $dateTo])->filter()->count()">
        <x-searchable-select id="task" :label="__('Task')" multiple wire:model.live="taskId" :placeholder="__('All tasks')" :all-label="__('All tasks')" :options="$taskOptions" />
        <x-searchable-select id="status" :label="__('Status')" multiple wire:model.live="status" :placeholder="__('All statuses')" :all-label="__('All statuses')"
            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-searchable-select id="priority" :label="__('Priority')" multiple wire:model.live="priority" :placeholder="__('All priorities')" :all-label="__('All priorities')"
            :options="collect($priorities)->mapWithKeys(fn ($p) => [$p->value => $p->label()])" />
        @if ($scope !== 'mine')
            <x-searchable-select id="employee" :label="__('Employee')" multiple wire:model.live="employeeId" :placeholder="__('All employees')" :all-label="__('All employees')"
                :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->user->name])" />
        @endif
        @if ($scope === 'all')
            <x-searchable-select id="department" :label="__('Department')" multiple wire:model.live="departmentId" :placeholder="__('All departments')" :all-label="__('All departments')"
                :options="$departments->pluck('name', 'id')" />
        @endif
        <x-date-input id="from" :label="__('Created from')" wire:model="dateFrom" compact />
        <x-date-input id="to" :label="__('Created to')" wire:model="dateTo" compact />
    </x-filter-bar>

    <x-data-table id="tasks-{{ $scope }}" :columns="$columns">
        @if ($tasks->isEmpty())
            <x-empty-state :title="__('No tasks found')" :text="$search || $taskId || $status || $priority || $employeeId || $departmentId || $dateFrom || $dateTo ? __('Try changing your filters or press Clear.') : __('Nothing here yet.')">
                @if ($scope === 'mine')
                    <x-button :href="route('tasks.create')" variant="primary" icon="plus">{{ __('Create your first task') }}</x-button>
                @endif
            </x-empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-sort-th field="title" :label="__('Task')" :sort="$sortField" :dir="$sortDirection" />
                            @if ($scope !== 'mine')<x-sort-th field="employee" col="employee" :label="__('Employee')" :sort="$sortField" :dir="$sortDirection" />@endif
                            <x-sort-th field="status" col="status" :label="__('Status')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="priority" col="priority" :label="__('Priority')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="due" col="due" :label="__('Due')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="created" col="created" :label="__('Created')" :sort="$sortField" :dir="$sortDirection" />
                            <x-th class="w-20 text-end last:rounded-se-lg">{{ __('Actions') }}</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <tr wire:key="task-{{ $task->id }}" class="group transition-colors odd:bg-white even:bg-slate-50/50 hover:bg-indigo-50/60">
                                <td class="border-s-4 border-transparent px-4 py-3 text-xs font-medium text-slate-400 transition group-hover:border-indigo-500">{{ $tasks->firstItem() + $loop->index }}</td>
                                <td class="max-w-xs px-4 py-3">
                                    <a href="{{ route('tasks.show', $task) }}" wire:navigate class="font-semibold text-slate-900 transition hover:text-indigo-600">{{ $task->title }}</a>
                                </td>
                                @if ($scope !== 'mine')
                                    <td data-col="employee" class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :user="$task->employee->user" size="sm" />
                                            <div>
                                                <p class="font-medium text-slate-800">{{ $task->employee->user->name }}</p>
                                                <p class="text-xs text-slate-400">{{ $task->employee->department->name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td data-col="status" class="px-4 py-3"><x-status-badge :status="$task->status" /></td>
                                <td data-col="priority" class="px-4 py-3"><x-priority-badge :priority="$task->priority" /></td>
                                <td data-col="due" class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td data-col="created" class="whitespace-nowrap px-4 py-3 text-slate-500" title="{{ $task->created_at->format('d/m/Y H:i') }}">{{ $task->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @include('livewire.tasks.partials.actions-menu', ['task' => $task])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
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
    </x-data-table>
</div>
