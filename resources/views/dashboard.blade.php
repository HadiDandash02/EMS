@php
    // heroicons-style paths used by the KPI cards
    $ico = [
        'tasks' => 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'alert' => 'M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'check' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    ];
    $icon = fn (string $k) => '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="'.$ico[$k].'"/></svg>';
@endphp
<x-layouts.app title="Dashboard">
    <div class="space-y-10">
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
                        {{ $user->roleLabels() }}@if ($user->employee) · {{ $user->employee->job_title }} · {{ $user->employee->department->name }}@endif
                    </p>
                </div>
                <a href="{{ route('profile') }}" wire:navigate class="ms-auto rounded-lg bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/25">{{ __('My profile') }}</a>
            </div>
        </div>

        {{-- ============ COMPANY (HR + super admin) ============ --}}
        @if ($company)
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $user->isSuperAdmin() ? __('Company overview') : __(':name department overview', ['name' => $user->employee?->department?->name]) }}</h3>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <x-stat :label="__('Employees')" :value="$company['numbers']['employees']" :href="route('employees.index')" tone="sky">{!! $icon('users') !!}</x-stat>
                    <x-stat :label="__('Total tasks')" :value="$company['headline']['total']" :href="route('tasks.all')" tone="violet">{!! $icon('tasks') !!}</x-stat>
                    <x-stat :label="__('Awaiting approval')" :value="$company['headline']['awaiting']" :href="route('tasks.all', ['status' => ['COMPLETED']])" tone="amber">{!! $icon('clock') !!}</x-stat>
                    <x-stat :label="__('Overdue')" :value="$company['headline']['overdue']" :hint="__('Due in 7 days').': '.$company['headline']['due_soon']" tone="rose">{!! $icon('alert') !!}</x-stat>
                    <x-stat :label="__('Completion rate')" :value="$company['headline']['completion']" suffix="%" :hint="$company['headline']['done'].' / '.$company['headline']['total']" tone="emerald">{!! $icon('check') !!}</x-stat>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <x-card :title="__('Tasks by status')"><x-chart type="doughnut" :labels="$company['status']['labels']" :values="$company['status']['values']" :colors="$company['status']['colors']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Tasks by priority')"><x-chart type="bar" :labels="$company['priority']['labels']" :values="$company['priority']['values']" :colors="$company['priority']['colors']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Approved vs rejected')" :hint="__('All decisions taken by managers')"><x-chart type="doughnut" :labels="$company['decisions']['labels']" :values="$company['decisions']['values']" :colors="$company['decisions']['colors']" :label="__('Decisions')" :empty="__('No decisions yet')" /></x-card>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-card :title="__('Tasks by department')"><x-chart type="bar" :labels="$company['perDepartment']['labels']" :values="$company['perDepartment']['values']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Tasks per employee')" :hint="__('Top 8')"><x-chart type="bar" :horizontal="true" :labels="$company['perEmployee']['labels']" :values="$company['perEmployee']['values']" :label="__('Tasks')" /></x-card>
                </div>

                <x-card :title="__('Tasks created in the last 30 days')">
                    <x-chart type="line" height="h-56" :labels="$company['trend']['labels']" :values="$company['trend']['values']" :label="__('Tasks created')" />
                </x-card>

                @if ($company['roles'])
                    <div class="grid gap-6 lg:grid-cols-3">
                        <x-card :title="__('Users by role')"><x-chart type="doughnut" :labels="$company['roles']['labels']" :values="$company['roles']['values']" :colors="['#6366f1', '#0ea5e9', '#10b981', '#f59e0b']" :label="__('Users')" /></x-card>
                        <div class="grid gap-4 lg:col-span-2 sm:grid-cols-2">
                            <x-stat :label="__('Users')" :value="$company['numbers']['users']" :href="route('admin.users')" tone="indigo">{!! $icon('users') !!}</x-stat>
                            <x-stat :label="__('Departments')" :value="$company['numbers']['departments']" :href="route('admin.departments')" tone="violet">{!! $icon('building') !!}</x-stat>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- ============ TEAM (managers) ============ --}}
        @if ($team)
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Team overview') }}</h3>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-stat :label="__('Team members')" :value="$team['members']" tone="sky">{!! $icon('users') !!}</x-stat>
                    <x-stat :label="__('Waiting for your approval')" :value="$team['headline']['awaiting']" :href="route('team.tasks')" tone="amber">{!! $icon('clock') !!}</x-stat>
                    <x-stat :label="__('Team tasks')" :value="$team['headline']['total']" :hint="__('Open').': '.$team['headline']['open']" tone="violet">{!! $icon('tasks') !!}</x-stat>
                    <x-stat :label="__('Overdue')" :value="$team['headline']['overdue']" :hint="__('Due in 7 days').': '.$team['headline']['due_soon']" tone="rose">{!! $icon('alert') !!}</x-stat>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <x-card :title="__('Team tasks by status')"><x-chart type="doughnut" :labels="$team['status']['labels']" :values="$team['status']['values']" :colors="$team['status']['colors']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Tasks per team member')"><x-chart type="bar" :horizontal="true" :labels="$team['perMember']['labels']" :values="$team['perMember']['values']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Approved vs rejected')" :hint="__('Your decisions')"><x-chart type="doughnut" :labels="$team['decisions']['labels']" :values="$team['decisions']['values']" :colors="$team['decisions']['colors']" :label="__('Decisions')" :empty="__('No decisions yet')" /></x-card>
                </div>
            </section>
        @endif

        {{-- ============ MY TASKS (everyone with an employee profile) ============ --}}
        @if ($mine)
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('My tasks') }}</h3>
                    <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('View all') }} →</a>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-stat :label="__('My tasks')" :value="$mine['headline']['total']" :href="route('tasks.index')" tone="violet">{!! $icon('tasks') !!}</x-stat>
                    <x-stat :label="__('Open')" :value="$mine['headline']['open']" :hint="__('Pending, in progress or rejected')" tone="sky">{!! $icon('clock') !!}</x-stat>
                    <x-stat :label="__('Overdue')" :value="$mine['headline']['overdue']" :hint="__('Due in 7 days').': '.$mine['headline']['due_soon']" tone="rose">{!! $icon('alert') !!}</x-stat>
                    <x-stat :label="__('Completion rate')" :value="$mine['headline']['completion']" suffix="%" :hint="$mine['headline']['done'].' / '.$mine['headline']['total']" tone="emerald">{!! $icon('check') !!}</x-stat>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach (\App\Enums\TaskStatus::cases() as $status)
                        <a href="{{ route('tasks.index', ['status' => [$status->value]]) }}" wire:navigate class="card p-4 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-300">
                            <x-status-badge :status="$status" />
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $mineCounts[$status->value] ?? 0 }}</p>
                        </a>
                    @endforeach
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <x-card :title="__('My tasks by status')"><x-chart type="doughnut" :labels="$mine['status']['labels']" :values="$mine['status']['values']" :colors="$mine['status']['colors']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('My tasks by priority')"><x-chart type="bar" :labels="$mine['priority']['labels']" :values="$mine['priority']['values']" :colors="$mine['priority']['colors']" :label="__('Tasks')" /></x-card>
                    <x-card :title="__('Tasks created in the last 30 days')"><x-chart type="line" :labels="$mine['trend']['labels']" :values="$mine['trend']['values']" :label="__('Tasks created')" /></x-card>
                </div>
            </section>
        @endif

        @if ($user->isSuperAdmin())
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Administration') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('admin.users') }}" wire:navigate class="card p-5 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">{{ __('Manage users') }} →</p><p class="mt-1 text-sm text-slate-500">{{ __('Create, edit and remove accounts.') }}</p>
                    </a>
                    <a href="{{ route('admin.departments') }}" wire:navigate class="card p-5 transition hover:shadow-md hover:ring-indigo-300">
                        <p class="font-medium text-slate-900">{{ __('Manage departments') }} →</p><p class="mt-1 text-sm text-slate-500">{{ __('Organise teams and departments.') }}</p>
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
