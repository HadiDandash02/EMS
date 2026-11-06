@props(['id', 'name', 'label', 'autocomplete' => 'current-password', 'error' => null])
{{-- Password field with a show/hide eye. Works as a plain input (name=) or with wire:model passed through. --}}
<div x-data="{ show: false }">
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <div class="relative mt-1">
        <input id="{{ $id }}" name="{{ $name }}" :type="show ? 'text' : 'password'" autocomplete="{{ $autocomplete }}"
               {{ $attributes->class(['block w-full rounded-lg border-0 py-2.5 pl-3 pr-11 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500', $error ? 'ring-rose-400' : 'ring-slate-300']) }}>
        <button type="button" @click="show = !show" tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-slate-400 hover:text-slate-600 focus:outline-none"
                :aria-label="show ? 'Hide password' : 'Show password'" :title="show ? 'Hide password' : 'Show password'">
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 8.2 7.36 5 12 5s8.58 3.2 9.96 6.68a1 1 0 010 .64C20.58 15.8 16.64 19 12 19s-8.58-3.2-9.96-6.68z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a3 3 0 004.2 4.2M9.9 5.2A9.8 9.8 0 0112 5c4.6 0 8.6 3.2 9.96 6.68a1 1 0 010 .64 10.6 10.6 0 01-2.7 3.8M6.2 6.3a10.7 10.7 0 00-4.16 5.38 1 1 0 000 .64C3.4 15.8 7.4 19 12 19c1.6 0 3.1-.4 4.4-1"/></svg>
        </button>
    </div>
    @if ($error)<p class="mt-1 text-sm text-rose-600">{{ $error }}</p>@endif
</div>
