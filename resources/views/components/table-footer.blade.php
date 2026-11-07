@props(['paginator'])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $window = collect([1, $last, $current - 1, $current, $current + 1])->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
    $btn = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500';
@endphp
{{-- Always-visible table footer: range, rows per page and numbered pages. Needs HasFilterBar + WithPagination on the component. --}}
<div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
        <span>
            @if ($paginator->total() > 0)
                {{ __('Showing') }} <strong class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</strong>
                {{ __('to') }} <strong class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</strong>
                {{ __('of') }} <strong class="font-semibold text-slate-700">{{ $paginator->total() }}</strong>
            @else
                {{ __('No results') }}
            @endif
        </span>
    </div>

    @if ($last > 1)
        <nav class="flex items-center gap-1" role="navigation" aria-label="{{ __('Page navigation') }}">
            <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) aria-label="{{ __('Previous') }}"
                    class="{{ $btn }} bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>

            @foreach ($window as $i => $page)
                @if ($i > 0 && $page - $window[$i - 1] > 1)<span class="px-1 text-slate-400">…</span>@endif
                @if ($page === $current)
                    <span aria-current="page" class="{{ $btn }} bg-linear-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/30">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }})" aria-label="{{ __('Page') }} {{ $page }}"
                            class="{{ $btn }} bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ $page }}</button>
                @endif
            @endforeach

            <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) aria-label="{{ __('Next') }}"
                    class="{{ $btn }} bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </nav>
    @endif
</div>
