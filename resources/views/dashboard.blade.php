<x-layouts.app title="Dashboard">
    <div class="space-y-8">
        {{-- Greeting banner --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg sm:p-8">
            <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="relative flex flex-wrap items-center gap-5">
                <x-avatar :user="$user" size="lg" class="!ring-4 !ring-white/30" />
                <div>
                    <p class="text-sm text-indigo-100">{{ now()->format('l, F j') }}</p>
                    <h2 class="text-2xl font-semibold">Hello, {{ $user->name }}</h2>
                    <p class="text-sm text-indigo-100">
                        {{ $user->role->label() }}@if ($user->employee) · {{ $user->employee->job_title }} · {{ $user->employee->department->name }}@endif
                    </p>
                </div>
                <a href="{{ route('profile') }}" wire:navigate class="ml-auto rounded-lg bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/25">Edit profile</a>
            </div>
        </div>

        @if ($company)
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Company overview</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['Employees', $company['employees'], route('hr.employees'), 'from-sky-500 to-indigo-500', 'M12 14a4 4 0 100-8 4 4 0 000 8zm-7 7a7 7 0 0114 0'],
                        ['Total tasks', $company['tasks'], route('hr.tasks'), 'from-violet-500 to-fuchsia-500', 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01'],
                        ['Awaiting approval', $company['awaiting'], route('hr.tasks', ['status' => 'COMPLETED']), 'from-amber-500 to-orange-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ] as [$label, $value, $link, $grad, $icon])
                        <a href="{{ $link }}" wire:navigate class="group flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-300">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </span>
                            <div>
                                <p class="text-sm text-slate-500">{{ $label }}</p>
                                <p class="text-3xl font-semibold text-slate-900">{{ $value }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($awaiting !== null)
            <a href="{{ route('team.tasks') }}" wire:navigate class="flex items-center justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-amber-200 transition hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <p class="text-sm text-slate-500">Waiting for your approval</p>
                        <p class="text-3xl font-semibold text-slate-900">{{ $awaiting }}</p>
                    </div>
                </div>
                <span class="text-sm font-medium text-indigo-600">Review →</span>
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
                        <a href="{{ route('tasks.index', ['status' => $status->value]) }}" wire:navigate class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-300">
                            <x-status-badge :status="$status" />
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $mine[$status->value] ?? 0 }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($user->isSuperAdmin())
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Administration</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('admin.users') }}" wire:navigate class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">Manage users →</p><p class="mt-1 text-sm text-slate-500">Create, edit and remove accounts.</p>
                    </a>
                    <a href="{{ route('admin.departments') }}" wire:navigate class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">Manage departments →</p><p class="mt-1 text-sm text-slate-500">Organise teams and departments.</p>
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
