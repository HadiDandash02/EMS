@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'show' => true],
        ['route' => 'tasks.index', 'label' => 'My tasks', 'icon' => 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01', 'show' => $user->employee !== null],
        ['route' => 'team.tasks', 'label' => 'Team tasks', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 110-8 4 4 0 010 8zm6 2a3 3 0 100-6M3 10a3 3 0 106 0', 'show' => $user->isManager()],
        ['route' => 'approvals.history', 'label' => 'Approval history', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'show' => $user->isManager() || $user->canSeeAll()],
        ['route' => 'hr.tasks', 'label' => 'All tasks', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'show' => $user->canSeeAll()],
        ['route' => 'hr.employees', 'label' => 'Employees', 'icon' => 'M12 14a4 4 0 100-8 4 4 0 000 8zm-7 7a7 7 0 0114 0', 'show' => $user->canSeeAll()],
    ];
    $admin = [
        ['route' => 'admin.users', 'label' => 'Users', 'icon' => 'M15 19.13a4 4 0 00-7.95-.63M12 13a4 4 0 100-8 4 4 0 000 8zm8 6v-1a4 4 0 00-3-3.87M17 5.13a4 4 0 010 7.74', 'show' => $user->isSuperAdmin()],
        ['route' => 'admin.departments', 'label' => 'Departments', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01', 'show' => $user->isSuperAdmin()],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'EMS' }} · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="min-h-full">
    {{-- Mobile backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-slate-900 to-indigo-950 text-slate-300 transition-transform lg:translate-x-0"
           :class="{ 'translate-x-0': sidebar }">
        <div class="flex h-16 items-center gap-3 px-6">
            <img src="{{ asset('favicon.svg') }}" alt="" class="h-9 w-9">
            <div class="leading-tight">
                <p class="text-sm font-semibold text-white">EMS</p>
                <p class="text-xs text-slate-400">Task &amp; Approvals</p>
            </div>
        </div>

        <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-3">
            @foreach ($nav as $item)
                @if ($item['show'])
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/10 text-white shadow-inner' : 'hover:bg-white/5 hover:text-white' }}">
                        <svg class="h-5 w-5 {{ $active ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach

            @if ($user->isSuperAdmin())
                <p class="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Administration</p>
                @foreach ($admin as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/10 text-white shadow-inner' : 'hover:bg-white/5 hover:text-white' }}">
                        <svg class="h-5 w-5 {{ $active ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="border-t border-white/10 p-4">
            <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 rounded-lg p-1.5 transition hover:bg-white/5">
                <x-avatar :user="$user" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $user->role->label() }} · &#64;{{ $user->username }}</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">Sign out</button>
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

            <div class="relative ml-auto" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 transition hover:bg-slate-100" aria-haspopup="true" :aria-expanded="open">
                    <x-avatar :user="$user" size="sm" />
                    <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ $user->name }}</span>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition @click.outside="open = false"
                     class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-slate-200">
                    <div class="px-3 py-2">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <div class="my-1 border-t border-slate-100"></div>
                    <a href="{{ route('profile') }}" wire:navigate class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">My profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <footer class="px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">EMS · Employee Task &amp; Approval Management</footer>
    </div>
</div>

{{-- Toasts: any component can fire $this->dispatch('notify', type: 'success', message: '...') --}}
<div x-data="{ toasts: [], add(d) { const t = { id: Date.now() + Math.random(), type: d.type ?? 'success', message: d.message }; this.toasts.push(t); setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== t.id), 5000); } }"
     @notify.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white px-4 py-3 text-sm shadow-lg"
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
