@props(['id', 'name', 'label', 'autocomplete' => 'current-password', 'error' => null, 'meter' => false])
{{--
    Password field with a show/hide eye. Works as a plain input (name=) or with wire:model passed through.
    With `meter`: a live strength bar + checklist that mirrors App\Support\PasswordPolicy
    (10+ characters, upper AND lower case, a number, a symbol).
--}}
<div x-data="{
        show: false, pw: '',
        get checks() {
            return [
                { ok: this.pw.length >= 10, label: @js(__('At least 10 characters')) },
                { ok: /[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw), label: @js(__('Upper and lower case letters')) },
                { ok: /[0-9]/.test(this.pw), label: @js(__('A number')) },
                { ok: /[^A-Za-z0-9]/.test(this.pw), label: @js(__('A symbol (e.g. ! @ # $)')) },
            ];
        },
        get score() { return this.checks.filter(c => c.ok).length; },
        get strength() { return [@js(__('Too weak')), @js(__('Weak')), @js(__('Fair')), @js(__('Good')), @js(__('Strong'))][this.score]; },
        get bar() { return ['bg-slate-200', 'bg-rose-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-500'][this.score]; },
     }"
     @password-reset.window="pw = ''"
     @password-generated.window="pw = $event.detail; $refs.input.value = $event.detail; show = true">
    <label for="{{ $id }}" class="block text-sm font-semibold text-slate-800">{{ $label }}</label>
    <div class="relative mt-1">
        <input id="{{ $id }}" name="{{ $name }}" x-ref="input" type="password" :type="show ? 'text' : 'password'" autocomplete="{{ $autocomplete }}"
               @if ($meter) @input="pw = $event.target.value" @endif
               {{ $attributes->class(['block w-full rounded-lg border-0 py-2.5 ps-3 pe-11 text-sm font-medium text-slate-900 placeholder:font-normal placeholder:text-slate-400 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500', $error ? 'ring-rose-400' : 'ring-slate-300']) }}>
        <button type="button" @click="show = !show" tabindex="-1"
                class="absolute inset-y-0 end-0 flex items-center rounded-e-lg px-3 text-slate-400 hover:text-slate-600 focus:outline-none"
                :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))" :title="show ? @js(__('Hide password')) : @js(__('Show password'))">
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 8.2 7.36 5 12 5s8.58 3.2 9.96 6.68a1 1 0 010 .64C20.58 15.8 16.64 19 12 19s-8.58-3.2-9.96-6.68z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a3 3 0 004.2 4.2M9.9 5.2A9.8 9.8 0 0112 5c4.6 0 8.6 3.2 9.96 6.68a1 1 0 010 .64 10.6 10.6 0 01-2.7 3.8M6.2 6.3a10.7 10.7 0 00-4.16 5.38 1 1 0 000 .64C3.4 15.8 7.4 19 12 19c1.6 0 3.1-.4 4.4-1"/></svg>
        </button>
    </div>

    @if ($meter)
        <div class="mt-2 space-y-2" x-show="pw.length > 0" x-cloak>
            <div class="flex items-center gap-2">
                <div class="grid flex-1 grid-cols-4 gap-1">
                    <template x-for="i in 4" :key="i"><span class="h-1.5 rounded-full transition-colors" :class="i <= score ? bar : 'bg-slate-200'"></span></template>
                </div>
                <span class="w-16 text-end text-xs font-semibold text-slate-600" x-text="strength"></span>
            </div>
            <ul class="grid gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                <template x-for="c in checks" :key="c.label">
                    <li class="flex items-center gap-1.5" :class="c.ok ? 'text-emerald-600' : 'text-slate-400'">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path x-show="c.ok" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/><circle x-show="!c.ok" cx="12" cy="12" r="4"/></svg>
                        <span x-text="c.label"></span>
                    </li>
                </template>
            </ul>
        </div>
    @endif

    @if ($error)<p class="mt-1 text-sm text-rose-600">{{ $error }}</p>@endif
</div>
