<x-page :title="__('Delete user')" :subtitle="__('This cannot be undone.')"
        :crumbs="[[__('Users'), route('admin.users')], [$user->name, null], [__('Delete user'), null]]">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" :title="__('Please confirm')" tone="danger">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <p class="text-sm text-slate-600">{{ __('Permanently delete') }} <strong class="text-slate-900">{{ $user->name }}</strong>? {{ __('This cannot be undone.') }}</p>
            </div>

            <x-slot:foot>
                <x-button :href="route('admin.users')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="button" variant="danger" icon="trash" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                    <span wire:loading.remove wire:target="delete">{{ __('Delete user') }}</span>
                    <span wire:loading wire:target="delete">{{ __('Please wait…') }}</span>
                </x-button>
            </x-slot:foot>
        </x-card>

        <x-card :title="__('Summary')">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <x-avatar :user="$user" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">&#64;{{ $user->username }}</p>
                </div>
                <x-role-badge :role="$user->role" class="ms-auto" />
            </div>
            <x-info-list class="mt-5" :items="[
                __('Email') => $user->email,
                __('Department') => $user->employee?->department->name ?? '—',
                __('Job title') => $user->employee?->job_title ?? '—',
            ]" />
        </x-card>
    </div>
</x-page>
