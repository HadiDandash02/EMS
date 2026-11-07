@php
    $columns = ['role' => __('Role'), 'department' => __('Department'), 'manager' => __('Manager'), 'tasks' => __('Tasks')];
@endphp
<div class="space-y-5">
    <x-filter-bar :placeholder="__('Name, email, number or job title…')" :label="__('Search employees')" :total="$employees->total()" noun="employee">
        <x-searchable-select id="emp-dept" :label="__('Department')" wire:model="departmentId" :placeholder="__('All departments')" :all-label="__('All departments')" :options="$departments->pluck('name', 'id')" />
    </x-filter-bar>

    <x-data-table id="employees" :columns="$columns">
        @if ($employees->isEmpty())
            <x-empty-state :title="__('No employees found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-sort-th field="name" :label="__('Employee')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="role" col="role" :label="__('Role')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="department" col="department" :label="__('Department')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="manager" col="manager" :label="__('Manager')" :sort="$sortField" :dir="$sortDirection" />
                            <x-sort-th field="tasks" col="tasks" :label="__('Tasks')" :sort="$sortField" :dir="$sortDirection" class="text-end last:rounded-se-lg" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employees as $e)
                            <tr wire:key="emp-{{ $e->id }}" class="group transition-colors odd:bg-white even:bg-slate-50/50 hover:bg-indigo-50/60">
                                <td class="border-s-4 border-transparent px-4 py-3 text-xs font-medium text-slate-400 transition group-hover:border-indigo-500">{{ $employees->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$e->user" />
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $e->user->name }} <span class="ms-1 text-xs font-normal text-slate-400">{{ $e->employee_number }}</span></p>
                                            <p class="text-xs text-slate-500">{{ $e->job_title }} · {{ $e->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td data-col="role" class="px-4 py-3"><x-role-badge :role="$e->user->role" /></td>
                                <td data-col="department" class="px-4 py-3 text-slate-600">{{ $e->department->name }}</td>
                                <td data-col="manager" class="px-4 py-3 text-slate-600">{{ $e->manager?->user->name ?? '—' }}</td>
                                <td data-col="tasks" class="px-4 py-3 text-end">
                                    <a href="{{ route('tasks.all', ['employeeId' => $e->id]) }}" wire:navigate class="inline-flex min-w-8 justify-center rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-100 transition hover:bg-indigo-100">{{ $e->tasks_count }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$employees" />
        @endif
    </x-data-table>
</div>
