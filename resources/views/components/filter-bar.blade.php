@props(['placeholder' => null, 'label' => null, 'total' => null, 'noun' => 'result', 'active' => 0])
@php $targets = 'search,clearFilters,gotoPage,nextPage,previousPage'; @endphp
{{--
    FILTER PANEL: one card. Every control (search box, dropdowns, dates) sits in an equal-width grid
    and has the same height. The whole panel collapses/expands with one button (remembered in the browser).
    Filters apply on "Search" / Enter; "Clear" resets them.
    The Livewire component must expose public search()/clearFilters() (see Concerns\HasFilterBar).
--}}
<form wire:submit="search"
      x-data="{
          open: (() => { try { return JSON.parse(localStorage.getItem('filters-open') ?? 'true'); } catch (e) { return true; } })(),
          toggle() { this.open = !this.open; try { localStorage.setItem('filters-open', JSON.stringify(this.open)); } catch (e) {} },
      }"
      class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">

    <div class="flex items-center justify-between gap-3 px-4 py-3">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
            </span>
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Filters') }}</h2>
            @if ($active > 0)<span class="rounded-full bg-indigo-600 px-2 text-[11px] font-bold leading-5 text-white">{{ $active }}</span>@endif
        </div>

        <x-button type="button" variant="secondary" size="sm" x-on:click="toggle()" ::aria-expanded="open">
            <span x-text="open ? @js(__('Collapse')) : @js(__('Expand'))"></span>
            <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open ? '' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
        </x-button>
    </div>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="border-t border-slate-100 p-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="fb-search" class="mb-1 block text-xs font-medium text-slate-600">{{ $label ?? __('Search') }}</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute start-3 top-3 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input id="fb-search" type="search" wire:model="search" placeholder="{{ $placeholder ?? __('Search…') }}" autocomplete="off"
                           class="block h-10 w-full rounded-lg border-0 bg-white ps-9 pe-3 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{ $slot }}
        </div>

        <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
            <span wire:loading wire:target="{{ $targets }}" class="inline-flex items-center text-indigo-600">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
            </span>
            <x-button type="button" variant="secondary" icon="x" wire:click="clearFilters">{{ __('Clear') }}</x-button>
            <x-button type="submit" variant="primary" icon="search">{{ __('Search') }}</x-button>
        </div>
    </div>
</form>
