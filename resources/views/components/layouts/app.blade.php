@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $dir = config("ems.locales.{$locale}.dir", 'ltr');

    // Sidebar menu (same list feeds the page search in the top bar)
    $nav = [
        ['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'show' => true],
        ['route' => 'tasks.index', 'label' => __('My tasks'), 'icon' => 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01', 'show' => $user->employee !== null],
        ['route' => 'team.tasks', 'label' => __('Team tasks'), 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 110-8 4 4 0 010 8zm6 2a3 3 0 100-6M3 10a3 3 0 106 0', 'show' => $user->isManager()],
        ['route' => 'approvals.history', 'label' => __('Approval history'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'show' => $user->isManager() || $user->canSeeAll()],
        ['route' => 'tasks.all', 'label' => __('All tasks'), 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'show' => $user->canSeeAll()],
        ['route' => 'employees.index', 'label' => __('Employees'), 'icon' => 'M12 14a4 4 0 100-8 4 4 0 000 8zm-7 7a7 7 0 0114 0', 'show' => $user->canSeeAll()],
    ];
    $admin = [
        ['route' => 'admin.users', 'label' => __('Users'), 'icon' => 'M15 19.13a4 4 0 00-7.95-.63M12 13a4 4 0 100-8 4 4 0 000 8zm8 6v-1a4 4 0 00-3-3.87M17 5.13a4 4 0 010 7.74', 'show' => $user->isSuperAdmin()],
        ['route' => 'admin.departments', 'label' => __('Departments'), 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01', 'show' => $user->isSuperAdmin()],
    ];

    // Pages the user can jump to with the search box
    $pages = collect([...$nav, ...$admin])->where('show', true)
        ->map(fn ($i) => ['label' => $i['label'], 'url' => route($i['route']), 'icon' => $i['icon']])
        ->push(['label' => __('My profile'), 'url' => route('profile'), 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0'])
        ->push(['label' => __('Change password'), 'url' => route('profile').'#change-password', 'icon' => 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.03 5.91l-2.16 2.16H9v2.25H6.75V19.5H4.5v-2.25l6.4-6.4A6 6 0 1121.75 8.25z'])
        ->values();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __($title ?? 'EMS') }} · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script>
        // Light / dark: applied before the page paints (no flash). Saved choice, otherwise the system setting.
        try {
            var t = localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
    <x-validation-messages />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="min-h-full">
    {{-- Mobile backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar (light, like the Employee Management project) --}}
    <aside class="fixed inset-y-0 start-0 z-40 flex w-64 flex-col border-e border-slate-200 bg-white transition-transform"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full lg:translate-x-0 lg:rtl:translate-x-0'">
        <a href="{{ route('dashboard') }}" wire:navigate class="block border-b border-slate-100 px-5 py-4" aria-label="TaskFlow">
            <x-logo :subtitle="__(':role panel', ['role' => $user->role->label()])" />
        </a>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            <p class="px-3 pb-1 pt-4 text-[11px] font-bold uppercase tracking-[.14em] text-slate-400">{{ __('Menu') }}</p>
            @foreach ($nav as $item)
                @if ($item['show'])
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900' }}">
                        @if ($active)<span class="absolute inset-y-2 -start-3 w-[3px] rounded-e bg-indigo-600"></span>@endif
                        <svg class="h-5 w-5 {{ $active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach

            @if ($user->isSuperAdmin())
                <p class="px-3 pb-1 pt-5 text-[11px] font-bold uppercase tracking-[.14em] text-slate-400">{{ __('Administration') }}</p>
                @foreach ($admin as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900' }}">
                        @if ($active)<span class="absolute inset-y-2 -start-3 w-[3px] rounded-e bg-indigo-600"></span>@endif
                        <svg class="h-5 w-5 {{ $active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        </nav>
    </aside>

    {{-- Main --}}
    <div class="lg:ps-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebar = true" aria-label="{{ __('Open menu') }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="min-w-0 shrink truncate text-base font-semibold text-slate-900 sm:text-lg">{{ __($title ?? 'EMS') }}</h1>

            {{-- Right side: page search · language · today's date · profile --}}
            <div class="ms-auto flex shrink-0 items-center gap-2">

                {{-- Search: tasks, people, departments (from the database, limited to what this user may see) and menu pages --}}
                @livewire('global-search', ['pages' => $pages->all()])

                {{-- Light / dark mode --}}
                <button type="button"
                        x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
                        @click="dark = !dark; document.documentElement.dataset.theme = dark ? 'dark' : 'light'; try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {} window.dispatchEvent(new Event('theme-changed'))"
                        :aria-label="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
                        :title="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-indigo-600">
                    <svg x-show="!dark" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    <svg x-show="dark" x-cloak class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                </button>

                {{-- Language --}}
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" @click="open = !open" aria-haspopup="true" :aria-expanded="open" title="{{ __('Language') }}"
                            class="flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3c2.5 2.4 3.9 5.6 3.9 9s-1.4 6.6-3.9 9c-2.5-2.4-3.9-5.6-3.9-9S9.5 5.4 12 3z"/></svg>
                        <span>{{ config("ems.locales.{$locale}.short") }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition class="select-scroll absolute end-0 mt-2 max-h-80 w-40 overflow-y-auto overscroll-contain rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-slate-200">
                        @foreach (config('ems.locales') as $code => $info)
                            <a href="{{ route('language', $code) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ $locale === $code ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                {{ $info['name'] }}
                                @if ($locale === $code)<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>@endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Today's date --}}
                <span class="hidden items-center gap-2 whitespace-nowrap rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200 md:inline-flex">
                    <span class="h-[7px] w-[7px] rounded-full bg-green-500 ring-[3px] ring-green-500/20"></span>
                    {{ now()->translatedFormat('l, d M Y') }}
                </span>

                {{-- Profile menu --}}
                <div class="relative" x-data="{ open: false, account: {{ request()->routeIs('profile') ? 'true' : 'false' }} }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" @click="open = !open" aria-haspopup="true" :aria-expanded="open" aria-label="{{ __('Profile menu') }}"
                            class="flex items-center gap-2.5 rounded-full bg-white py-1 ps-1 pe-3 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                        <x-avatar :user="$user" size="sm" />
                        <span class="hidden min-w-0 max-w-[11rem] flex-col text-start leading-tight xl:flex">
                            <span class="truncate text-[13px] font-bold text-slate-900">{{ $user->name }}</span>
                            <span class="text-[11px] font-semibold text-indigo-600">{{ __($user->role->label()) }}</span>
                        </span>
                        <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition class="select-scroll absolute end-0 mt-2 max-h-[80vh] overflow-y-auto overscroll-contain rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200" style="width:17rem">
                        <div class="border-b border-slate-100 px-3 pb-2.5 pt-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">&#64;{{ $user->username }} · {{ $user->email }}</p>
                        </div>

                        <button type="button" @click="account = !account" :aria-expanded="account"
                                class="mt-1 flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium text-slate-700 hover:bg-slate-50">
                            <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0"/></svg>
                            {{ __('My account & password') }}
                            <svg class="ms-auto h-3.5 w-3.5 text-slate-400 transition" :class="account ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="account" x-cloak x-transition class="my-1 ms-6 border-s-2 border-slate-200 ps-3">
                            <a href="{{ route('profile') }}" wire:navigate class="block rounded-md px-2.5 py-1.5 text-[13px] {{ request()->routeIs('profile') ? 'font-semibold text-indigo-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">{{ __('My profile') }}</a>
                            <a href="{{ route('profile') }}#change-password" class="block rounded-md px-2.5 py-1.5 text-[13px] text-slate-600 hover:bg-slate-50 hover:text-slate-900">{{ __('Change password') }}</a>
                        </div>

                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium text-rose-600 hover:bg-rose-50">
                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{-- Messages appear INSIDE the page (no floating popups): saved / error after a form, or from a component --}}
            @php
                $flash = collect(['success', 'error'])->filter(fn ($k) => session()->has($k))
                    ->map(fn ($k) => ['type' => $k, 'message' => session($k)])->values();
            @endphp
            <div x-data="{ items: @js($flash), add(d) { this.items = [{ type: d.type ?? 'success', message: d.message }]; this.$nextTick(() => this.$el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })); } }"
                 @notify.window="add($event.detail)" x-show="items.length" x-cloak class="mb-5">
                <template x-for="(a, i) in items" :key="i">
                    <div role="alert" class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm"
                         :class="a.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path x-show="a.type !== 'error'" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path x-show="a.type === 'error'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.01"/>
                        </svg>
                        <p class="flex-1 font-medium" x-text="a.message"></p>
                        <button type="button" @click="items = []" class="-m-1 rounded p-1 opacity-60 transition hover:opacity-100" aria-label="{{ __('Close') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            {{ $slot }}
        </main>

        <footer class="px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">{{ __('Employee Task & Approval Management') }}</footer>
    </div>
</div>

@livewireScripts
</body>
</html>
