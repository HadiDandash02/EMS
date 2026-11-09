<x-page :title="__('Delete translation')" :subtitle="__('This cannot be undone.')"
        :crumbs="[[__('General Configurations'), null], [__('Translations'), route('admin.translations')], [__('Delete translation'), null]]">
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" :title="__('Please confirm')" tone="danger">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm text-slate-600">{{ __('Permanently delete the translation of') }} <strong class="text-slate-900 [overflow-wrap:anywhere]">{{ $translation->key }}</strong>?</p>
                    @if ($translation->type === 'code')
                        <p class="mt-2 text-sm text-slate-500">{{ __('This key is used in the code: the interface will show the English text until it is translated again.') }}</p>
                    @endif
                </div>
            </div>

            <x-slot:foot>
                <x-button :href="route('admin.translations')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="button" variant="danger" icon="trash" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                    <span wire:loading.remove wire:target="delete">{{ __('Delete translation') }}</span>
                    <span wire:loading wire:target="delete">{{ __('Please wait…') }}</span>
                </x-button>
            </x-slot:foot>
        </x-card>

        <x-card :title="__('Summary')">
            <x-info-list :items="[__('Key') => $translation->key, __('English') => $translation->en ?: '—', __('Arabic') => $translation->ar ?: '—', __('Type') => $translation->type === 'keyword' ? __('Keyword') : __('Code')]" />
        </x-card>
    </div>
</x-page>