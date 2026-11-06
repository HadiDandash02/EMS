@props(['user', 'size' => 'md'])
@php
    $sizes = ['xs' => 'h-6 w-6 text-[10px]', 'sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-14 w-14 text-lg', 'xl' => 'h-24 w-24 text-3xl'];
    $palette = [
        'from-indigo-500 to-violet-500', 'from-sky-500 to-indigo-500', 'from-emerald-500 to-teal-500',
        'from-rose-500 to-orange-400', 'from-amber-500 to-pink-500', 'from-fuchsia-500 to-purple-500',
    ];
    $gradient = $palette[crc32($user->name) % count($palette)];
    $url = $user->avatarUrl();
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ $user->name }}" loading="lazy"
         {{ $attributes->class([$sizes[$size] ?? $sizes['md'], 'shrink-0 rounded-full object-cover ring-2 ring-white']) }}>
@else
    <span title="{{ $user->name }}" aria-label="{{ $user->name }}"
          {{ $attributes->class([$sizes[$size] ?? $sizes['md'], "inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br {$gradient} font-semibold text-white ring-2 ring-white select-none"]) }}>
        {{ $user->initials() }}
    </span>
@endif
