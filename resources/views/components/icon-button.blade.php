@props(['icon', 'title', 'href' => null, 'tone' => 'default'])
@php
    $icons = [
        'eye' => 'M2.04 12.32a1 1 0 010-.64C3.42 8.2 7.36 5 12 5s8.58 3.2 9.96 6.68a1 1 0 010 .64C20.58 15.8 16.64 19 12 19s-8.58-3.2-9.96-6.68zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'pencil' => 'M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z',
        'trash' => 'M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0',
    ];
    $tones = ['default' => 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200', 'danger' => 'text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200'];
    $class = 'inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white ring-1 ring-slate-200 shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 '.($tones[$tone] ?? $tones['default']);
@endphp
{{-- Small square icon button for table rows (the eye / pencil of the reference tables) --}}
@if ($href)
    <a href="{{ $href }}" wire:navigate title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->class([$class]) }}>
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}"/></svg>
    </a>
@else
    <button type="button" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->class([$class]) }}>
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}"/></svg>
    </button>
@endif
