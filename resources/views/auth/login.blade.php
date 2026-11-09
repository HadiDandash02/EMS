@php
    $locale = app()->getLocale();
    $dir = config("ems.locales.{$locale}.dir", 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign in') }} · {{ config('app.name') }}</title>
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
</head>
<body class="h-full font-sans text-slate-800 antialiased">
<div class="grid min-h-full lg:grid-cols-2">
    {{-- Brand panel --}}
    <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-900 p-12 text-white lg:flex">
        <x-logo size="lg" dark class="relative z-10" />
        <div class="relative z-10">
            <x-illustration class="mx-auto w-full max-w-md drop-shadow-2xl" />
            <h2 class="mt-8 text-3xl font-semibold leading-tight">{{ __('Employee Task & Approval Management') }}</h2>
            <p class="mt-3 max-w-md text-indigo-200/80">{{ __('Track work, submit it for review and keep a complete audit trail of every approval decision.') }}</p>
        </div>
        <p class="relative z-10 text-sm text-indigo-300/60">{{ __('Built with Laravel · Livewire · Tailwind CSS') }}</p>
    </div>

    {{-- Form --}}
    <div class="relative flex items-center justify-center px-6 py-12">
        {{-- Language switch (visitors can choose before signing in) --}}
        <div class="absolute end-6 top-6 flex items-center gap-1 rounded-lg bg-white p-1 text-sm shadow-sm ring-1 ring-slate-200">
            <button type="button" x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
                    @click="dark = !dark; document.documentElement.dataset.theme = dark ? 'dark' : 'light'; try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}"
                    :aria-label="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))" :title="dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
                    class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-indigo-600">
                <svg x-show="!dark" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                <svg x-show="dark" x-cloak class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
            </button>
            @foreach (config('ems.locales') as $code => $info)
                <a href="{{ route('language', $code) }}" class="rounded-md px-2.5 py-1 font-medium {{ $locale === $code ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $info['name'] }}</a>
            @endforeach
        </div>

        <div class="w-full max-w-sm">
            <x-logo class="mb-8 lg:hidden" />
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('Welcome back') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Sign in with your username or email.') }}</p>

            @if (session('error'))
                <div role="alert" class="mt-6 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.01"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" novalidate data-validate class="mt-8 space-y-5">
                <x-form-errors />
                @csrf
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700">{{ __('Username or email') }}</label>
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0"/></svg>
                        </span>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                               class="block w-full rounded-lg border-0 py-2.5 ps-10 pe-3 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 {{ $errors->has('login') ? 'ring-rose-400' : 'ring-slate-300' }}">
                    </div>
                    @error('login')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-password-input id="password" name="password" :label="__('Password')" required :error="$errors->first('password')" />

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> {{ __('Remember me') }}
                </label>
                <x-button type="submit" variant="primary" size="lg" class="w-full">{{ __('Sign in') }}</x-button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
