@props(['title' => null, 'hint' => null, 'tone' => null, 'flush' => false])
{{--
    THE standard white box. Zones: head (title + hint + optional "tools" slot), body, optional "foot" (buttons).
    Look = .card / .card-head / .card-body / .card-foot in resources/css/app.css.
    tone="danger" tints the head red (used by destructive pages). flush = no body padding (tables, lists).
--}}
<section {{ $attributes->class('card') }}>
    @if ($title || isset($tools))
        <header class="card-head {{ $tone === 'danger' ? 'card-head-danger' : '' }}">
            <div class="min-w-0">
                @if ($title)<h3 class="card-title">{{ $title }}</h3>@endif
                @if ($hint)<p class="card-hint">{{ $hint }}</p>@endif
            </div>
            @isset($tools)<div class="flex shrink-0 items-center gap-2">{{ $tools }}</div>@endisset
        </header>
    @endif

    <div @class(['card-body' => ! $flush])>{{ $slot }}</div>

    @isset($foot)<footer class="card-foot">{{ $foot }}</footer>@endisset
</section>
