@php $yesNo = ['yes' => __('Yes'), 'no' => __('No')]; @endphp
<x-page :title="__('Translations')" :subtitle="__('Every text of the interface is stored here, in the database. Edit the English and Arabic text and press Save: the whole system uses it at once.')"
        :crumbs="[[__('General Configurations'), null], [__('Translations'), null]]">
    <x-slot:actions>
        <x-button type="button" variant="secondary" icon="plus" wire:click="$toggle('adding')">{{ __('Add keyword') }}</x-button>
        <x-button type="button" variant="secondary" icon="search" wire:click="scanCode" wire:loading.attr="disabled" wire:target="scanCode">
            <span wire:loading.remove wire:target="scanCode">{{ __('Scan the code for new texts') }}</span>
            <span wire:loading wire:target="scanCode">{{ __('Please wait…') }}</span>
        </x-button>
        {{-- one click translates ALL the empty texts: the server handles a batch, this loop asks again until nothing is left --}}
        <div x-data="{ running: false, left: {{ $emptyCount }}, failed: false,
                       async run() {
                           this.running = true; this.failed = false;
                           while (this.running) {
                               const left = await $wire.translateMissing();
                               if (left < 0) { this.failed = true; break; }
                               this.left = left;
                               if (left === 0) break;
                           }
                           this.running = false;
                           $dispatch('notify', this.failed
                               ? { type: 'error', message: @js(__('The translation service did not answer. Try again later.')) }
                               : { type: 'success', message: this.left === 0 ? @js(__('Every text is already translated.')) : @js(__('Stopped.')) });
                       } }"
             class="flex items-center gap-2">
            <span x-show="running" x-cloak class="text-sm font-medium text-indigo-600"><span x-text="left"></span> {{ __('texts left') }}</span>
            <x-button type="button" variant="secondary" size="sm" x-show="running" x-cloak x-on:click="running = false">{{ __('Stop') }}</x-button>
            <x-button type="button" variant="primary" icon="check" x-bind:disabled="running" x-on:click="run()">
                <span x-show="! running">{{ __('Auto-translate ALL empty texts') }} ({{ $emptyCount }})</span>
                <span x-show="running" x-cloak>{{ __('Translating…') }}</span>
            </x-button>
        </div>
    </x-slot:actions>

    @if ($adding)
        <x-card :title="__('Add keyword')" :hint="__('A keyword is a key you create by hand. Use it in the code with the translation helper.')">
            <form wire:submit="addKeyword" novalidate data-validate class="grid gap-4 md:grid-cols-3">
                @foreach ([['newKey', __('Key')], ['newEn', __('English')], ['newAr', __('Arabic')]] as [$field, $label])
                    <div>
                        <label for="tr-{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}@if ($field === 'newKey') * @endif</label>
                        <input id="tr-{{ $field }}" type="text" wire:model="{{ $field }}" @if ($field === 'newKey') required maxlength="500" @else maxlength="2000" @endif @if ($field === 'newAr') dir="rtl" lang="ar" @endif
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error($field) ring-rose-400 @else ring-slate-300 @enderror">
                        @error($field)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="flex justify-end gap-2 md:col-span-3">
                    <x-button type="button" variant="secondary" icon="x" wire:click="$set('adding', false)">{{ __('Cancel') }}</x-button>
                    <x-button type="submit" variant="primary" icon="save">{{ __('Save') }}</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-filter-bar :placeholder="__('Search a key, an English or an Arabic text…')" :label="__('Search')" :total="$rows->total()" noun="text"
                  :active="collect([$inEnglish, $inArabic, $type])->filter()->count()">
        <x-searchable-select id="tr-en" :label="__('Translated To English')" multiple wire:model.live="inEnglish" :placeholder="__('All')" :all-label="__('All')" :options="$yesNo" />
        <x-searchable-select id="tr-ar" :label="__('Translated To Arabic')" multiple wire:model.live="inArabic" :placeholder="__('All')" :all-label="__('All')" :options="$yesNo" />
        <x-searchable-select id="tr-type" :label="__('Type')" multiple wire:model.live="type" :placeholder="__('All')" :all-label="__('All')" :options="['code' => __('Code'), 'keyword' => __('Keyword')]" />
    </x-filter-bar>

    <x-data-table id="translations" :columns="['type' => __('Type')]" :exportable="false">
        @if ($rows->isEmpty())
            <x-empty-state :title="__('No texts found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-th>{{ __('Key') }}</x-th>
                            <x-th>{{ __('English') }}</x-th>
                            <x-th>{{ __('Arabic') }}</x-th>
                            <x-th col="type">{{ __('Type') }}</x-th>
                            <x-th class="w-44 text-end last:rounded-se-lg">{{ __('Actions') }}</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $t)
                            <tr wire:key="tr-{{ $t->id }}" class="align-top odd:bg-white even:bg-slate-50/50">
                                <td class="px-4 py-3 text-xs font-medium text-slate-400">{{ $rows->firstItem() + $loop->index }}</td>
                                <td class="max-w-xs px-4 py-3 text-xs text-slate-600 [overflow-wrap:anywhere]">{{ $t->key }}</td>
                                <td class="min-w-[16rem] px-4 py-3">
                                    <textarea wire:model="edits.{{ $t->id }}.en" rows="2" lang="en" dir="ltr"
                                              class="block w-full rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('edits.'.$t->id.'.en') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                                    @error('edits.'.$t->id.'.en')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </td>
                                <td class="min-w-[16rem] px-4 py-3">
                                    <textarea wire:model="edits.{{ $t->id }}.ar" rows="2" dir="rtl" lang="ar" placeholder="{{ __('Not translated yet') }}"
                                              class="block w-full rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('edits.'.$t->id.'.ar') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                                    @error('edits.'.$t->id.'.ar')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                                    @if ($t->machine && $t->ar)<span class="mt-1 inline-block rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">{{ __('Machine') }}</span>@endif
                                </td>
                                <td data-col="type" class="px-4 py-3">
                                    <span class="rounded-md px-2 py-0.5 text-xs font-semibold {{ $t->type === 'keyword' ? 'bg-fuchsia-50 text-fuchsia-700' : 'bg-slate-100 text-slate-700' }}">{{ $t->type === 'keyword' ? __('Keyword') : __('Code') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-button type="button" variant="soft" size="sm" wire:click="suggest({{ $t->id }})" wire:loading.attr="disabled" wire:target="suggest({{ $t->id }})">{{ __('Suggest') }}</x-button>
                                        <x-button type="button" variant="primary" size="sm" icon="save" wire:click="save({{ $t->id }})" wire:loading.attr="disabled" wire:target="save({{ $t->id }})">{{ __('Save') }}</x-button>
                                        @if ($t->type === 'keyword')
                                            <x-button type="button" variant="soft-danger" size="sm" icon="trash" wire:click="deleteKeyword({{ $t->id }})" wire:loading.attr="disabled" aria-label="{{ __('Delete') }}"></x-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$rows" />
        @endif
    </x-data-table>
</x-page>
