<div class="mx-auto max-w-xl space-y-5">
    <x-button :href="route('admin.users')" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ __('Delete user') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Permanently delete') }} <strong class="text-slate-900">{{ $user->name }}</strong>? {{ __('This cannot be undone.') }}</p>
            </div>
        </div>

        <div class="px-6 py-5">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <x-avatar :user="$user" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }} <span class="font-normal text-slate-400">&#64;{{ $user->username }}</span></p>
                    <p class="truncate text-xs text-slate-500">{{ $user->email }}@if ($user->employee) · {{ $user->employee->department->name }}@endif</p>
                </div>
                <x-role-badge :role="$user->role" class="ms-auto" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.users')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="button" variant="danger" icon="trash" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete">{{ __('Delete user') }}</span>
                <span wire:loading wire:target="delete">{{ __('Please wait…') }}</span>
            </x-button>
        </div>
    </div>
</div>
