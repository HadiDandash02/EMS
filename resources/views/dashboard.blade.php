<x-layouts.app title="Dashboard">
    <div class="space-y-8">
        {{-- Greeting banner --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg sm:p-8">
            <div class="absolute -end-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-16 end-24 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="relative flex flex-wrap items-center gap-5">
                <x-avatar :user="$user" size="lg" class="!ring-4 !ring-white/30" />
                <div>
                    <p class="text-sm text-indigo-100">{{ now()->translatedFormat('l, d/m/Y') }}</p>
                    <h2 class="text-2xl font-semibold">{{ __('Hello,') }} {{ $user->name }}</h2>
                    <p class="text-sm text-indigo-100">
                        {{ $user->role->label() }}@if ($user->employee) · {{ $user->employee->job_title }} · {{ $user->employee->department->name }}@endif
                    </p>
                </div>
                <a href="{{ route('profile') }}" wire:navigate class="ms-auto rounded-lg bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/25">{{ __('Edit profile') }}</a>
            </div>
        </div>

        @if ($company)
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Company overview') }}</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        [__('Employees'), $company['employees'], route('employees.index'), 'from-sky-500 to-indigo-500', 'M12 14a4 4 0 100-8 4 4 0 000 8zm-7 7a7 7 0 0114 0'],
                        [__('Total tasks'), $company['tasks'], route('tasks.all'), 'from-violet-500 to-fuchsia-500', 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01'],
                        [__('Awaiting approval'), $company['awaiting'], route('tasks.all', ['status' => 'COMPLETED']), 'from-amber-500 to-orange-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
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
                        <p class="text-sm text-slate-500">{{ __('Waiting for your approval') }}</p>
                        <p class="text-3xl font-semibold text-slate-900">{{ $awaiting }}</p>
                    </div>
                </div>
                <span class="text-sm font-medium text-indigo-600">{{ __('Review') }} →</span>
            </a>
        @endif

        @if ($user->employee)
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('My tasks') }}</h3>
                    <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('View all') }} →</a>
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
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Administration') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('admin.users') }}" wire:navigate class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">{{ __('Manage users') }} →</p><p class="mt-1 text-sm text-slate-500">{{ __('Create, edit and remove accounts.') }}</p>
                    </a>
                    <a href="{{ route('admin.departments') }}" wire:navigate class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">{{ __('Manage departments') }} →</p><p class="mt-1 text-sm text-slate-500">{{ __('Organise teams and departments.') }}</p>
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
