@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'show' => true],
        ['route' => 'tasks.index', 'label' => 'My tasks', 'icon' => 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01', 'show' => true],
        ['route' => 'team.tasks', 'label' => 'Team tasks', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 110-8 4 4 0 010 8zm6 2a3 3 0 100-6M3 10a3 3 0 106 0', 'show' => $user->isManager()],
        ['route' => 'approvals.history', 'label' => 'Approval history', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'show' => $user->isManager() || $user->isHr()],
        ['route' => 'hr.tasks', 'label' => 'All tasks', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'show' => $user->isHr()],
        ['route' => 'hr.employees', 'label' => 'Employees', 'icon' => 'M12 14a4 4 0 100-8 4 4 0 000 8zm-7 7a7 7 0 0114 0', 'show' => $user->isHr()],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'EMS' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="min-h-full">
    {{-- Mobile backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform lg:translate-x-0"
           :class="{ 'translate-x-0': sidebar }">
        <div class="flex h-16 items-center gap-3 px-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500 text-sm font-bold text-white">EM</span>
            <div class="leading-tight">
                <p class="text-sm font-semibold text-white">EMS</p>
                <p class="text-xs text-slate-400">Task &amp; Approvals</p>
            </div>
        </div>

        <nav class="mt-4 flex-1 space-y-1 px-3">
            @foreach ($nav as $item)
                @if ($item['show'])
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-slate-800 text-white' : 'hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="h-5 w-5 {{ $active ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-slate-800 p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-semibold text-indigo-300">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400">{{ $user->role->label() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Sign out</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebar = true" aria-label="Open menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'EMS' }}</h1>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Toasts: any component can fire $this->dispatch('notify', type: 'success', message: '...') --}}
<div x-data="{ toasts: [], add(d) { const t = { id: Date.now() + Math.random(), type: d.type ?? 'success', message: d.message }; this.toasts.push(t); setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== t.id), 4500); } }"
     @notify.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-white px-4 py-3 text-sm shadow-lg"
             :class="t.type === 'error' ? 'border-rose-200' : 'border-emerald-200'" role="status">
            <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
            <span class="text-slate-700" x-text="t.message"></span>
        </div>
    </template>
</div>

@livewire('tasks.task-form')
@livewire('tasks.reject-modal')
@livewireScripts
</body>
</html>
