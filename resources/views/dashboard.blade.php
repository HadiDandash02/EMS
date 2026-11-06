<x-layouts.app title="Dashboard">
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Hello, {{ $user->name }} 👋</h2>
            <p class="text-sm text-slate-500">
                {{ $user->employee?->job_title }} · {{ $user->employee?->department?->name }}
            </p>
        </div>

        @if ($company)
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Company overview</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([['Employees', $company['employees'], route('hr.employees')], ['Total tasks', $company['tasks'], route('hr.tasks')], ['Awaiting approval', $company['awaiting'], route('hr.tasks', ['status' => 'COMPLETED'])]] as [$label, $value, $link])
                        <a href="{{ $link }}" wire:navigate class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:ring-indigo-300">
                            <p class="text-sm text-slate-500">{{ $label }}</p>
                            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $value }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($awaiting !== null)
            <a href="{{ route('team.tasks') }}" wire:navigate class="flex items-center justify-between rounded-xl bg-indigo-600 p-5 text-white shadow-sm transition hover:bg-indigo-500">
                <div>
                    <p class="text-sm text-indigo-100">Waiting for your approval</p>
                    <p class="mt-1 text-3xl font-semibold">{{ $awaiting }}</p>
                </div>
                <span class="text-sm font-medium">Review →</span>
            </a>
        @endif

        @if ($user->employee)
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">My tasks</h3>
                <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all →</a>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                    <a href="{{ route('tasks.index', ['status' => $status->value]) }}" wire:navigate class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:ring-indigo-300">
                        <x-status-badge :status="$status" />
                        <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $mine[$status->value] ?? 0 }}</p>
                    </a>
                @endforeach
            </div>
        </section>
        @endif
    </div>
</x-layouts.app>
