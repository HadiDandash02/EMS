@props(['variant' => 'primary', 'size' => null, 'icon' => null, 'href' => null, 'type' => 'button', 'navigate' => true])
@php
    // Heroicons-style outline paths
    $icons = [
        'plus' => 'M12 5v14M5 12h14',
        'check' => 'M4.5 12.75l6 6 9-13.5',
        'save' => 'M5 13l4 4L19 7',
        'x' => 'M6 18L18 6M6 6l12 12',
        'trash' => 'M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0',
        'pencil' => 'M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z',
        'search' => 'M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z',
        'key' => 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.03 5.91l-2.16 2.16H9v2.25H6.75V19.5H4.5v-2.25l6.4-6.4A6 6 0 1121.75 8.25z',
        'back' => 'M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18',
        'send' => 'M6 12L3.27 3.13a.75.75 0 011.04-.89l16.5 8.25a.75.75 0 010 1.34l-16.5 8.25a.75.75 0 01-1.04-.9L6 12zm0 0h7.5',
        'play' => 'M5.25 5.65v12.7a.75.75 0 001.14.64l10.5-6.35a.75.75 0 000-1.28L6.39 5.01a.75.75 0 00-1.14.64z',
        'ban' => 'M18.36 5.64a9 9 0 11-12.73 0 9 9 0 0112.73 0zM5.64 5.64l12.72 12.72',
    ];
    $classes = ['btn', "btn-{$variant}", $size ? "btn-{$size}" : ''];
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        @if ($icon && isset($icons[$icon]))<svg class="{{ $size === 'sm' ? 'h-4 w-4' : 'h-[18px] w-[18px]' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}"/></svg>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon && isset($icons[$icon]))<svg class="{{ $size === 'sm' ? 'h-4 w-4' : 'h-[18px] w-[18px]' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}"/></svg>@endif
        {{ $slot }}
    </button>
@endif
