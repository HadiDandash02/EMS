@props(['subtitle' => null, 'size' => 'md', 'dark' => false])
@php
    $mark = ['sm' => 'h-8 w-8', 'md' => 'h-10 w-10', 'lg' => 'h-14 w-14'][$size] ?? 'h-10 w-10';
    $text = ['sm' => 'text-base', 'md' => 'text-lg', 'lg' => 'text-2xl'][$size] ?? 'text-lg';
@endphp
{{-- TaskFlow logo: gradient tile with a check leaving a progress ring ("work flows to done") + wordmark --}}
<span {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    <img src="{{ asset('logo.svg') }}" alt="TaskFlow" class="{{ $mark }} shrink-0 rounded-xl shadow-lg shadow-indigo-500/30">
    <span class="leading-tight">
        <span class="block {{ $text }} font-extrabold tracking-tight {{ $dark ? 'text-white' : 'bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 bg-clip-text text-transparent' }}">TaskFlow</span>
        @if ($subtitle)<span class="block text-[11px] font-medium {{ $dark ? 'text-indigo-200/80' : 'text-slate-500' }}">{{ $subtitle }}</span>@endif
    </span>
</span>
