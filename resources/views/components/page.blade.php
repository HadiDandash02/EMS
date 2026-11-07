@props(['title', 'subtitle' => null, 'crumbs' => []])
{{--
    THE standard page shell (full width): breadcrumbs -> title (+ subtitle, + optional "actions" slot) -> content.
    Every page of the system is built with it, so spacing/width/typography are changed in ONE place:
    the .page / .page-title classes in resources/css/app.css.

    <x-page :title="__('Task')" :crumbs="[[__('Tasks'), $url], [__('Task'), null]]">
        <x-slot:actions> ...buttons... </x-slot:actions>
        ...cards...
    </x-page>
--}}
<div {{ $attributes->class('page') }}>
    @if ($crumbs)<x-breadcrumbs :items="$crumbs" />@endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="page-title">{{ $title }}</h2>
            @if ($subtitle)<p class="page-subtitle">{{ $subtitle }}</p>@endif
            @isset($meta)<div class="mt-2 flex flex-wrap items-center gap-2">{{ $meta }}</div>@endisset
        </div>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>

    {{ $slot }}
</div>
