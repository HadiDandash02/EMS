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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">
<div class="grid min-h-full lg:grid-cols-2">
    {{-- Brand panel --}}
    <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-900 p-12 text-white lg:flex">
        <div class="relative z-10 flex items-center gap-3">
            <img src="{{ asset('favicon.svg') }}" alt="" class="h-10 w-10">
            <span class="text-lg font-semibold tracking-tight">EMS</span>
        </div>
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
            @foreach (config('ems.locales') as $code => $info)
                <a href="{{ route('language', $code) }}" class="rounded-md px-2.5 py-1 font-medium {{ $locale === $code ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $info['name'] }}</a>
            @endforeach
        </div>

        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('favicon.svg') }}" alt="" class="h-10 w-10"><span class="text-lg font-semibold">EMS</span>
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('Welcome back') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Sign in with your username or email.') }}</p>

            <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
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
                <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Sign in') }}</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
