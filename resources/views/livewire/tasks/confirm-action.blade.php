@php
    // full class names (Tailwind cannot see names assembled at runtime)
    $tone = ['primary' => 'bg-indigo-100 text-indigo-600', 'success' => 'bg-emerald-100 text-emerald-600', 'warning' => 'bg-amber-100 text-amber-600', 'danger' => 'bg-rose-100 text-rose-600'][$def['variant']] ?? 'bg-indigo-100 text-indigo-600';
@endphp
<div class="mx-auto max-w-xl space-y-5">
    <x-button :href="route('tasks.show', $task)" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $tone }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ __($def['title']) }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __($def['text']) }}</p>
            </div>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <x-avatar :user="$task->employee->user" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                    <p class="text-xs text-slate-500">{{ $task->employee->user->name }} · {{ $task->employee->department->name }}</p>
                </div>
                <x-status-badge :status="$task->status" class="ms-auto" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <x-button :href="route('tasks.show', $task)" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="button" :variant="$def['variant']" :icon="$def['icon']" wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm">
                <span wire:loading.remove wire:target="confirm">{{ __($def['button']) }}</span>
                <span wire:loading wire:target="confirm">{{ __('Please wait…') }}</span>
            </x-button>
        </div>
    </div>
</div>
