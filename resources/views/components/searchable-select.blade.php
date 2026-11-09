@props(['id', 'options' => [], 'placeholder' => null, 'label' => null, 'allLabel' => null, 'error' => false, 'multiple' => false, 'required' => false])
@php
    $model = $attributes->wire('model')->value();
    $live = $attributes->wire('model')->hasModifier('live');   // wire:model.live -> sync on every pick
    // Normalise: ['value' => 'Label'] or collection of [value,label] -> [{value,label}]
    $items = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values();
@endphp
{{--
    A select you can SEARCH: click, type to filter, pick. Bound to a Livewire property with wire:model.
    $options = ['value' => 'Label', ...]. $allLabel = text of the "no filter" row (omit to hide it).
    multiple = MULTI-SELECT: tick as many rows as you like (the property is a list); the list stays open.
    The list has its own scrollbar (see .select-scroll in resources/css/app.css) when there are many rows.
--}}
{{-- the key changes when the OPTIONS change (e.g. managers of the chosen department), so the list is rebuilt with the new options --}}
<div class="relative" data-select-host wire:key="ss-{{ $id }}-{{ md5($items->toJson()) }}"
     x-data="{
         open: false, q: '', multiple: @js((bool) $multiple), value: $wire.entangle('{{ $model }}'){{ $live ? '.live' : '' }}, options: @js($items), hl: 0,
         get vals() { return Array.isArray(this.value) ? this.value.map(String) : (this.value ? [String(this.value)] : []); },
         has(v) { return this.vals.includes(String(v)); },
         get filtered() { const q = this.q.trim().toLowerCase(); return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options; },
         get current() {
             const sel = this.options.filter(o => this.has(o.value));
             if (!sel.length) return null;
             if (!this.multiple || sel.length === 1) return sel[0].label;
             return sel.length <= 2 ? sel.map(o => o.label).join(', ') : sel.length + ' ' + @js(__('selected'));
         },
         toggle() { this.open = !this.open; if (this.open) { this.q = ''; this.hl = 0; this.$nextTick(() => this.$refs.q.focus()); } },
         pick(v) {
             if (this.multiple) { this.value = this.has(v) ? this.vals.filter(x => x !== String(v)) : [...this.vals, String(v)]; return; }
             this.value = v; this.open = false;
         },
         clear() { this.value = this.multiple ? [] : ''; if (!this.multiple) this.open = false; },
         move(d) { const n = this.filtered.length; if (n) this.hl = (this.hl + d + n) % n; },
         choose() { const o = this.filtered[this.hl]; if (o) this.pick(o.value); },
     }"
     @keydown.escape.window="open = false" @click.outside="open = false">
    @if ($label)<label for="{{ $id }}" class="mb-1 block text-xs font-medium text-slate-600">{{ $label }}</label>@endif
    <button type="button" id="{{ $id }}" @click="toggle()" aria-haspopup="listbox" :aria-expanded="open"
            class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border-0 bg-white ps-3 pe-2 text-start text-sm ring-1 ring-inset focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $error ? 'ring-rose-400' : 'ring-slate-300' }}">
        <span class="truncate" :class="current === null ? 'text-slate-500' : 'text-slate-900'" x-text="current ?? @js($placeholder ?? __('Select…'))"></span>
        <span class="flex shrink-0 items-center gap-1">
            <span x-show="multiple && vals.length > 1" x-cloak class="rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold leading-5 text-white" x-text="vals.length"></span>
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
        </span>
    </button>

    @if ($required)
        {{-- invisible mirror of the chosen value: lets resources/js/form-validation.js treat the select as a required field --}}
        <input type="text" tabindex="-1" aria-hidden="true" id="{{ $id }}-value" name="{{ $id }}-value" required :value="vals.join(',')"
               class="pointer-events-none absolute inset-x-0 top-0 h-10 w-full opacity-0">
    @endif

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
         class="absolute z-30 mt-1 w-full min-w-[12rem] overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
        <div class="border-b border-slate-100 p-2">
            <input x-ref="q" x-model="q" type="text" placeholder="{{ __('Type to search…') }}" autocomplete="off"
                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()"
                   @input="hl = 0"
                   class="block w-full rounded-md border-0 px-2.5 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
        </div>
        <ul class="select-scroll max-h-64 overflow-y-scroll overscroll-contain py-1 text-sm" role="listbox" @if ($multiple) aria-multiselectable="true" @endif>
            @if ($allLabel)
                <li x-show="q.trim() === ''" role="option" @click="clear()"
                    class="cursor-pointer px-3 py-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-700" :class="{ 'font-medium text-indigo-700': vals.length === 0 }">{{ $allLabel }}</li>
            @endif
            <template x-for="(o, i) in filtered" :key="o.value">
                <li role="option" @click="pick(o.value)" @mouseenter="hl = i" :aria-selected="has(o.value)"
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-slate-800"
                    :class="{ 'bg-indigo-50 text-indigo-700': hl === i, 'font-medium': has(o.value) }">
                    <span class="flex min-w-0 items-center gap-2">
                        <span x-show="multiple" x-cloak class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                              :class="has(o.value) ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white'">
                            <svg x-show="has(o.value)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span class="truncate" x-text="o.label"></span>
                    </span>
                    <svg x-show="! multiple && has(o.value)" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-center text-slate-400">{{ __('No matches') }}</li>
        </ul>
    </div>
</div>
