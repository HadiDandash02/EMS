@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => 'indigo', 'suffix' => null])
@php
    // full class names (Tailwind cannot see names assembled at runtime)
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600', 'sky' => 'bg-sky-50 text-sky-600', 'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600', 'emerald' => 'bg-emerald-50 text-emerald-600', 'violet' => 'bg-violet-50 text-violet-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
{{-- One headline number (KPI card). Look: .card + the tone classes above; used on every dashboard. --}}
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['card flex items-center gap-4 p-5', 'transition hover:shadow-md hover:ring-indigo-300' => $href]) }}>
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['indigo'] }}">{{ $slot }}</span>
    <div class="min-w-0">
        <p class="text-sm leading-snug text-slate-500">{{ $label }}</p>
        <p class="text-3xl font-semibold leading-tight text-slate-900">{{ $value }}@if ($suffix)<span class="ms-0.5 text-lg font-medium text-slate-400">{{ $suffix }}</span>@endif</p>
        @if ($hint)<p class="truncate text-xs text-slate-400">{{ $hint }}</p>@endif
    </div>
</{{ $tag }}>
