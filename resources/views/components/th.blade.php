@props(['col' => null])
{{-- Plain (not sortable) header cell that matches <x-sort-th> --}}
<th scope="col" @if ($col) data-col="{{ $col }}" @endif {{ $attributes->class(['px-4 py-3 text-start font-semibold uppercase tracking-wide text-white/85']) }}>{{ $slot }}</th>
