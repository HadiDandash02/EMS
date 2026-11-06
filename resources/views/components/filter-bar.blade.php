@props(['placeholder' => 'Search…', 'label' => 'Search', 'total' => null, 'noun' => 'result'])
@php $targets = 'search,clearFilters,gotoPage,nextPage,previousPage'; @endphp
{{--
    Filters are applied only when the user presses "Search" (or Enter) - and "Clear" resets everything.
    The Livewire component must expose public search()/clearFilters() (see Concerns\HasFilterBar).
--}}
<form wire:submit="search" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-600" for="fb-search">{{ $label }}</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="fb-search" type="search" wire:model="search" placeholder="{{ $placeholder }}" autocomplete="off"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        {{ $slot }}
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
        <p class="text-sm text-slate-500">
            @if ($total !== null)
                <span wire:loading.remove wire:target="{{ $targets }}">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural($noun, $total) }}</span>
            @endif
            <span wire:loading wire:target="{{ $targets }}" class="inline-flex items-center gap-2 text-indigo-600">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
                Loading…
            </span>
        </p>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="clearFilters"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                Clear
            </button>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
        </div>
    </div>
</form>
