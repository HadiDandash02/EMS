@props(['role'])
{{-- Role pill with a coloured dot (same look as the Employee Management project) --}}
@php
    $style = match ($role->value) {
        'super_admin' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'hr' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'manager' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        default => 'bg-teal-50 text-teal-700 ring-teal-200',
    };
@endphp
<span {{ $attributes->class(["inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset whitespace-nowrap {$style}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $role->label() }}
</span>
