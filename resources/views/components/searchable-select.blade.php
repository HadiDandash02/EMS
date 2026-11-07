@props(['id', 'options' => [], 'placeholder' => null, 'label' => null, 'allLabel' => null, 'error' => false])
@php
    $model = $attributes->wire('model')->value();
    $live = $attributes->wire('model')->hasModifier('live');   // wire:model.live -> sync on every pick
    // Normalise: ['value' => 'Label'] or collection of [value,label] -> [{value,label}]
    $items = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values();
@endphp
{{--
    A select you can SEARCH: click, type to filter, pick. Bound to a Livewire property with wire:model.
    $options = ['value' => 'Label', ...]. $allLabel = text of the "no filter" row (omit to hide it).
--}}
<div class="relative" wire:key="ss-{{ $id }}"
     x-data="{
         open: false, q: '', value: $wire.entangle('{{ $model }}'){{ $live ? '.live' : '' }}, options: @js($items), hl: 0,
         get filtered() { const q = this.q.trim().toLowerCase(); return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options; },
         get current() { const o = this.options.find(o => String(o.value) === String(this.value ?? '')); return o ? o.label : null; },
         toggle() { this.open = !this.open; if (this.open) { this.q = ''; this.hl = 0; this.$nextTick(() => this.$refs.q.focus()); } },
         pick(v) { this.value = v; this.open = false; },
         move(d) { const n = this.filtered.length; if (n) this.hl = (this.hl + d + n) % n; },
         choose() { const o = this.filtered[this.hl]; if (o) this.pick(o.value); },
     }"
     @keydown.escape.window="open = false" @click.outside="open = false">
    @if ($label)<label for="{{ $id }}" class="mb-1 block text-xs font-medium text-slate-600">{{ $label }}</label>@endif
    <button type="button" id="{{ $id }}" @click="toggle()" aria-haspopup="listbox" :aria-expanded="open"
            class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border-0 bg-white ps-3 pe-2 text-start text-sm ring-1 ring-inset focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $error ? 'ring-rose-400' : 'ring-slate-300' }}">
        <span class="truncate" :class="current === null ? 'text-slate-500' : 'text-slate-900'" x-text="current ?? @js($placeholder ?? __('Select…'))"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
         class="absolute z-30 mt-1 w-full min-w-[12rem] overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
        <div class="border-b border-slate-100 p-2">
            <input x-ref="q" x-model="q" type="text" placeholder="{{ __('Type to search…') }}" autocomplete="off"
                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()"
                   @input="hl = 0"
                   class="block w-full rounded-md border-0 px-2.5 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
        </div>
        <ul class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox">
            @if ($allLabel)
                <li x-show="q.trim() === ''" role="option" @click="pick('')"
                    class="cursor-pointer px-3 py-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-700" :class="{ 'font-medium text-indigo-700': !value }">{{ $allLabel }}</li>
            @endif
            <template x-for="(o, i) in filtered" :key="o.value">
                <li role="option" @click="pick(o.value)" @mouseenter="hl = i"
                    class="flex cursor-pointer items-center justify-between px-3 py-2 text-slate-800"
                    :class="{ 'bg-indigo-50 text-indigo-700': hl === i, 'font-medium': String(o.value) === String(value ?? '') }">
                    <span class="truncate" x-text="o.label"></span>
                    <svg x-show="String(o.value) === String(value ?? '')" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-center text-slate-400">{{ __('No matches') }}</li>
        </ul>
    </div>
</div>
