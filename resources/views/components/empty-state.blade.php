@props(['title', 'text' => null])
<div class="px-6 py-16 text-center">
    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 012-2z"/></svg>
    <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($text)<p class="mt-1 text-sm text-slate-500">{{ $text }}</p>@endif
    <div class="mt-4">{{ $slot }}</div>
</div>
