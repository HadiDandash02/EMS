<div class="space-y-5">
    <x-filter-bar :placeholder="__('Name, email, number or job title…')" :label="__('Search employees')" :total="$employees->total()" noun="employee">
        <x-searchable-select id="emp-dept" :label="__('Department')" wire:model="departmentId" :placeholder="__('All departments')" :all-label="__('All departments')" :options="$departments->pluck('name', 'id')" />
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($employees->isEmpty())
            <x-empty-state :title="__('No employees found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100/70 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Employee') }}</th><th class="px-4 py-3 text-start">{{ __('Role') }}</th><th class="px-4 py-3 text-start">{{ __('Department') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Manager') }}</th><th class="px-4 py-3 text-end">{{ __('Tasks') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employees as $e)
                            <tr wire:key="emp-{{ $e->id }}" class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$e->user" />
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $e->user->name }} <span class="ms-1 text-xs font-normal text-slate-400">{{ $e->employee_number }}</span></p>
                                            <p class="text-xs text-slate-500">{{ $e->job_title }} · {{ $e->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-role-badge :role="$e->user->role" /></td>
                                <td class="px-4 py-3 text-slate-600">{{ $e->department->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $e->manager?->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('tasks.all', ['employeeId' => $e->id]) }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-500">{{ $e->tasks_count }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$employees" />
        @endif
    </div>
</div>
