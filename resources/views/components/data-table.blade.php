@props(['id', 'columns' => [], 'exportable' => true])
{{--
    Professional table shell: a toolbar (search inside the table, rows per page, show/hide columns, export)
    above the table. Put the <table> (and <x-table-footer>) in the slot. Cells opt in to the column chooser
    with data-col="key" (header AND body cells).
    The component needs HasFilterBar (rows per page) and, for Export, SortsAndExports.
--}}
<div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200"
     x-data="{
         shown: $wire.entangle('searched'), open: true, q: '', hidden: [], key: 'table-cols-{{ $id }}',
         init() {
             this.$watch('shown', v => { if (v) this.open = true; });   // a new search always reopens the table
             try { this.hidden = JSON.parse(localStorage.getItem(this.key) || '[]'); } catch (e) {}
             this.$watch('hidden', v => { try { localStorage.setItem(this.key, JSON.stringify(v)); } catch (e) {} });
             // after every Livewire refresh (sorting, paging...) re-apply the quick search to the new rows
             if (window.Livewire) Livewire.hook('commit', ({ succeed }) => succeed(() => this.$nextTick(() => this.filter())));
         },
         toggle(c) { this.hidden = this.hidden.includes(c) ? this.hidden.filter(x => x !== c) : [...this.hidden, c]; },
         filter() {
             const q = this.q.trim().toLowerCase();
             this.$root.querySelectorAll('tbody tr').forEach(tr => { tr.hidden = q !== '' && !tr.textContent.toLowerCase().includes(q); });
         },
     }"
     x-show="shown" x-cloak
     :data-hide="hidden.join(' ')">

    {{-- Results card: shown by default (shown = Livewire "searched"); its button collapses/expands the table (open) --}}
    <div class="flex items-center justify-between gap-3 px-4 py-3" :class="open ? 'border-b border-slate-100' : ''">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/></svg>
            </span>
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Results') }}</h2>
        </div>
        <x-button type="button" variant="secondary" size="sm" x-on:click="open = !open" ::aria-expanded="open">
            <span x-text="open ? @js(__('Collapse')) : @js(__('Expand'))"></span>
            <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open ? '' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
        </x-button>
    </div>

    <div x-show="open" x-cloak>
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <svg class="pointer-events-none absolute start-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-model="q" @input="filter()" placeholder="{{ __('Search table') }}" aria-label="{{ __('Search table') }}" autocomplete="off"
                       class="block w-44 rounded-lg border-0 bg-white py-2 ps-9 pe-3 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 sm:w-56">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-500">
                <span class="sr-only sm:not-sr-only">{{ __('Rows per page') }}</span>
                <select wire:model.live="perPage" aria-label="{{ __('Rows per page') }}"
                        class="rounded-lg border-0 bg-white py-2 ps-3 pe-8 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
                    @foreach ([10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                </select>
            </label>
        </div>

        <div class="flex items-center gap-2">
            @if (count($columns))
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <x-button type="button" variant="secondary" size="sm" x-on:click="open = !open" aria-haspopup="true" ::aria-expanded="open">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M4.5 9h15m-15 6h15M6 4.5h12A1.5 1.5 0 0119.5 6v12a1.5 1.5 0 01-1.5 1.5H6A1.5 1.5 0 014.5 18V6A1.5 1.5 0 016 4.5z"/></svg>
                        {{ __('Columns') }}
                    </x-button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                         class="select-scroll absolute end-0 z-30 mt-2 max-h-80 w-52 overflow-y-auto overscroll-contain rounded-xl bg-white p-2 shadow-xl ring-1 ring-slate-200">
                        <p class="px-2 pb-1 pt-0.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Show columns') }}</p>
                        @foreach ($columns as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                       :checked="!hidden.includes('{{ $key }}')" @change="toggle('{{ $key }}')">
                                {{ $label }}
                            </label>
                        @endforeach
                        <button type="button" class="mt-1 w-full rounded-lg px-2 py-1.5 text-start text-xs font-medium text-indigo-600 hover:bg-indigo-50" @click="hidden = []">{{ __('Show all') }}</button>
                    </div>
                </div>
            @endif

            @if ($exportable)
                <x-button type="button" variant="secondary" size="sm" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span wire:loading.remove wire:target="exportCsv">{{ __('Export') }}</span>
                    <span wire:loading wire:target="exportCsv">{{ __('Exporting…') }}</span>
                </x-button>
            @endif
        </div>
    </div>

    <div class="data-table-body">
        {{ $slot }}
    </div>
    </div>
</div>
