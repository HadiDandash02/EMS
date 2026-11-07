@php
    // full class names (Tailwind cannot see names assembled at runtime)
    $tone = ['primary' => 'bg-indigo-100 text-indigo-600', 'success' => 'bg-emerald-100 text-emerald-600', 'warning' => 'bg-amber-100 text-amber-600', 'danger' => 'bg-rose-100 text-rose-600'][$def['variant']] ?? 'bg-indigo-100 text-indigo-600';
@endphp
<x-page :title="__($def['title'])" :subtitle="__($def['text'])"
        :crumbs="[[__('Tasks'), $task->listUrlFor(auth()->user())], [$task->title, route('tasks.show', $task)], [__($def['title']), null]]">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" :title="__('Please confirm')" :tone="$def['variant'] === 'danger' ? 'danger' : null">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $tone }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-base font-semibold text-slate-900">{{ $task->title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ __($def['text']) }}</p>
                </div>
            </div>

            <x-slot:foot>
                <x-button :href="route('tasks.show', $task)" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="button" :variant="$def['variant']" :icon="$def['icon']" wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm">
                    <span wire:loading.remove wire:target="confirm">{{ __($def['button']) }}</span>
                    <span wire:loading wire:target="confirm">{{ __('Please wait…') }}</span>
                </x-button>
            </x-slot:foot>
        </x-card>

        <aside>@include('livewire.tasks.partials.summary', ['task' => $task])</aside>
    </div>
</x-page>
