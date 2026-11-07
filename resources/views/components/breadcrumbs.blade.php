@props(['items' => []])
{{-- Breadcrumb trail: [[label, url|null], ...]. The last item is the current page. --}}
<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
    @foreach ($items as [$label, $url])
        @if (! $loop->first)
            <svg class="h-4 w-4 text-slate-300 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        @endif
        @if ($url && ! $loop->last)
            <a href="{{ $url }}" wire:navigate class="transition hover:text-indigo-600">{{ $label }}</a>
        @else
            <span class="font-medium text-slate-800" @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
        @endif
    @endforeach
</nav>
