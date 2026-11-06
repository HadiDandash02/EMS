<div class="space-y-5">
    <x-filter-bar placeholder="Name, email, number or job title…" label="Search employees" :total="$employees->total()" noun="employee">
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="emp-dept" class="mb-1 block text-xs font-medium text-slate-600">Department</label>
            <select id="emp-dept" wire:model="departmentId" class="block w-full rounded-lg border-0 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
                <option value="">All departments</option>
                @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
        </div>
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($employees->isEmpty())
            <x-empty-state title="No employees found" text="Try a different search or press Clear." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Employee</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Manager</th><th class="px-4 py-3 text-right">Tasks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employees as $e)
                            <tr wire:key="emp-{{ $e->id }}" class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$e->user" />
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $e->user->name }} <span class="ml-1 text-xs font-normal text-slate-400">{{ $e->employee_number }}</span></p>
                                            <p class="text-xs text-slate-500">{{ $e->job_title }} · {{ $e->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $e->user->role->label() }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ $e->department->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $e->manager?->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('hr.tasks', ['employeeId' => $e->id]) }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-500">{{ $e->tasks_count }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($employees->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $employees->links() }}</div>@endif
        @endif
    </div>
</div>
