@props(['show' => false, 'title', 'subtitle' => null, 'close' => 'close', 'maxWidth' => 'max-w-lg'])
@if ($show)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center" role="dialog" aria-modal="true"
         x-data x-on:keydown.escape.window="$wire.{{ $close }}()">
        <div class="max-h-[92vh] w-full {{ $maxWidth }} overflow-y-auto rounded-2xl bg-white shadow-2xl" x-on:click.outside="$wire.{{ $close }}()">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @if ($subtitle)<p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            {{ $slot }}
        </div>
    </div>
@endif
