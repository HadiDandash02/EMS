@props(['field', 'label', 'sort' => '', 'dir' => 'asc', 'col' => null])
@php $active = $sort === $field; @endphp
{{-- Sortable column header: click to sort ascending, click again for descending (needs SortsAndExports on the component). --}}
<th scope="col" @if ($col) data-col="{{ $col }}" @endif
    aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    {{ $attributes->class(['px-4 py-3 text-start']) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')" title="{{ __('Sort by :column', ['column' => $label]) }}"
            class="group inline-flex items-center gap-1.5 rounded font-semibold uppercase tracking-wide transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 {{ $active ? 'text-white' : 'text-white/85' }}">
        {{ $label }}
        <span class="flex flex-col gap-px" aria-hidden="true">
            <svg class="h-2 w-2.5 transition {{ $active && $dir === 'asc' ? 'text-white' : 'text-white/35 group-hover:text-white/70' }}" viewBox="0 0 10 6" fill="currentColor"><path d="M5 0l5 6H0z"/></svg>
            <svg class="h-2 w-2.5 transition {{ $active && $dir === 'desc' ? 'text-white' : 'text-white/35 group-hover:text-white/70' }}" viewBox="0 0 10 6" fill="currentColor"><path d="M5 6L0 0h10z"/></svg>
        </span>
    </button>
</th>
