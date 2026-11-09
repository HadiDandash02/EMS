<x-page :title="$translationId ? __('Edit translation') : __('Add keyword')"
        :subtitle="$translationId ? __('Change the English and the Arabic text. The key is what the code looks up, so it cannot be changed.') : __('A keyword is a key you create by hand. Use it in the code with the translation helper.')"
        :crumbs="[[__('General Configurations'), null], [__('Translations'), route('admin.translations')], [$translationId ? __('Edit translation') : __('Add keyword'), null]]">
    <x-form-errors />

    <form wire:submit="save" novalidate data-validate>
        <x-card :title="__('Translation')" :hint="__('Fields marked * are required.')">
            <div class="grid gap-5 md:grid-cols-3">
                <div class="md:col-span-3">
                    <label for="tr-key" class="block text-sm font-medium text-slate-700">{{ __('Key') }} @unless ($translationId)<span class="text-rose-500">*</span>@endunless</label>
                    <input id="tr-key" type="text" wire:model="key" maxlength="500" @if ($translationId) readonly @else required autofocus @endif
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('key') ring-rose-400 @else ring-slate-300 @enderror {{ $translationId ? 'bg-slate-50 text-slate-500' : '' }}">
                    @error('key')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-3 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="tr-en" class="block text-sm font-medium text-slate-700">{{ __('English') }}</label>
                        <textarea id="tr-en" rows="4" wire:model="en" maxlength="2000" lang="en" dir="ltr"
                                  class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('en') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                        @error('en')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="tr-ar" class="block text-sm font-medium text-slate-700">{{ __('Arabic') }}</label>
                        <textarea id="tr-ar" rows="4" wire:model="ar" maxlength="2000" lang="ar" dir="rtl"
                                  class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('ar') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                        @error('ar')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <x-slot:foot>
                <x-button :href="route('admin.translations')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $translationId ? __('Save changes') : __('Add keyword') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </x-button>
            </x-slot:foot>
        </x-card>
    </form>
</x-page>