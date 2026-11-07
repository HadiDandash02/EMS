@props(['id', 'label' => null, 'min' => null, 'max' => null, 'error' => false, 'compact' => false])
@php $model = $attributes->wire('model')->value(); @endphp
{{--
    Date picker that always displays dd/mm/yyyy (the native <input type="date"> follows the
    computer's locale and cannot be forced). The Livewire property keeps the ISO value yyyy-mm-dd.
    Usage: <x-date-input id="x" wire:model="due_date" label="Due date" :min="now()->toDateString()" />
--}}
<div wire:ignore
     x-data="{
         value: $wire.entangle('{{ $model }}'),
         fp: null,
         init() {
             this.fp = flatpickr(this.$refs.input, {
                 dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: false,
                 altInputClass: this.$refs.input.className, disableMobile: true,
                 minDate: @js($min), maxDate: @js($max), defaultDate: this.value || null,
                 onChange: (dates, str) => { this.value = str; },
             });
             this.$watch('value', v => this.fp.setDate(v || null, false));
         },
         destroy() { this.fp?.destroy(); },
     }">
    @if ($label)<label for="{{ $id }}" class="{{ $compact ? 'mb-1 block text-xs font-medium text-slate-600' : 'block text-sm font-medium text-slate-700' }}">{{ $label }}</label>@endif
    <input x-ref="input" id="{{ $id }}" type="text" placeholder="{{ __('dd/mm/yyyy') }}" readonly
           {{ $attributes->whereDoesntStartWith('wire:')->class(['block h-10 w-full cursor-pointer rounded-lg border-0 bg-white px-3 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500', $label && ! $compact ? 'mt-1' : '', $error ? 'ring-rose-400' : 'ring-slate-300']) }}>
</div>
