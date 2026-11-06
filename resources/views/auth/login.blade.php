<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">
<div class="grid min-h-full lg:grid-cols-2">
    <div class="hidden flex-col justify-between bg-slate-900 p-12 text-white lg:flex">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500 font-bold">EM</span>
            <span class="text-lg font-semibold">EMS</span>
        </div>
        <div>
            <h2 class="text-3xl font-semibold leading-tight">Employee Task &amp;<br>Approval Management</h2>
            <p class="mt-4 max-w-md text-slate-400">Track work, submit it for review and keep a complete audit trail of every approval decision.</p>
        </div>
        <p class="text-sm text-slate-500">Built with Laravel · Livewire · Tailwind CSS</p>
    </div>

    <div class="flex items-center justify-center px-6 py-12" x-data="{ email: '{{ old('email') }}', password: '' }">
        <div class="w-full max-w-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Welcome back</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to your account to continue.</p>

            <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" x-model="email" required autofocus autocomplete="username"
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500 @error('email') ring-rose-400 @enderror">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" x-model="password" required autocomplete="current-password"
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Remember me
                </label>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Sign in</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
