@props(['id', 'columns' => [], 'exportable' => true])
{{--
    Professional table shell: a toolbar (search inside the table, rows per page, show/hide columns, export)
    above the table. Put the <table> (and <x-table-footer>) in the slot. Cells opt in to the column chooser
    with data-col="key" (header AND body cells).
    The component needs HasFilterBar (rows per page) and, for Export, SortsAndExports.
--}}
<div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200"
     x-data="{
         q: '', hidden: [], key: 'table-cols-{{ $id }}',
         init() {
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
     :data-hide="hidden.join(' ')">

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
                         class="absolute end-0 z-30 mt-2 w-52 rounded-xl bg-white p-2 shadow-xl ring-1 ring-slate-200">
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

    <div class="data-table-body" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage,perPage,sortBy">
        {{ $slot }}
    </div>
</div>
