@php $columns = ['state' => __('Status')]; @endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <p class="max-w-3xl text-sm text-slate-500">
            {{ __('Every text of the interface is stored here, in the database. Edit the Arabic text and press Save: the whole system uses it at once. Empty texts are shown in English.') }}
        </p>
        <div class="flex flex-wrap gap-2">
            <x-button type="button" variant="secondary" icon="search" wire:click="scanCode" wire:loading.attr="disabled" wire:target="scanCode">
                <span wire:loading.remove wire:target="scanCode">{{ __('Scan the code for new texts') }}</span>
                <span wire:loading wire:target="scanCode">{{ __('Please wait…') }}</span>
            </x-button>
            <x-button type="button" variant="primary" icon="check" wire:click="translateMissing" wire:loading.attr="disabled" wire:target="translateMissing">
                <span wire:loading.remove wire:target="translateMissing">{{ __('Auto-translate empty texts') }}</span>
                <span wire:loading wire:target="translateMissing">{{ __('Translating…') }}</span>
            </x-button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('All texts')" :value="$counts['all']" tone="indigo"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg></x-stat>
        <x-stat :label="__('Not translated yet')" :value="$counts['missing']" tone="amber"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></x-stat>
        <x-stat :label="__('Machine translated (to review)')" :value="$counts['machine']" tone="violet"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5m4.75-11.396c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg></x-stat>
    </div>

    <x-filter-bar :placeholder="__('Search an English or Arabic text…')" :label="__('Search')" :total="$rows->total()" noun="text" :active="collect([$show])->filter()->count()">
        <x-searchable-select id="tr-show" :label="__('Show')" multiple wire:model.live="show" :placeholder="__('All texts')" :all-label="__('All texts')"
            :options="['untranslated' => __('Not translated yet'), 'machine' => __('Machine translated (to review)')]" />
    </x-filter-bar>

    <x-data-table id="translations" :columns="$columns" :exportable="false">
        @if ($rows->isEmpty())
            <x-empty-state :title="__('No texts found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 text-xs">
                        <tr>
                            <x-th class="w-12 first:rounded-ss-lg">#</x-th>
                            <x-th>{{ __('English text') }}</x-th>
                            <x-th>{{ __('Arabic text') }}</x-th>
                            <x-th col="state">{{ __('Status') }}</x-th>
                            <x-th class="w-44 text-end last:rounded-se-lg">{{ __('Actions') }}</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $t)
                            <tr wire:key="tr-{{ $t->id }}" class="align-top odd:bg-white even:bg-slate-50/50">
                                <td class="px-4 py-3 text-xs font-medium text-slate-400">{{ $rows->firstItem() + $loop->index }}</td>
                                <td class="max-w-sm px-4 py-3 text-slate-800">{{ $t->key }}</td>
                                <td class="min-w-[18rem] px-4 py-3">
                                    <textarea wire:model="edits.{{ $t->id }}" rows="2" dir="rtl" lang="ar" placeholder="{{ __('Not translated yet') }}"
                                              class="block w-full rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('edits.'.$t->id) ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                                    @error('edits.'.$t->id)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </td>
                                <td data-col="state" class="px-4 py-3">
                                    @if ($t->value === null || $t->value === '')
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('Not translated yet') }}</span>
                                    @elseif ($t->machine)
                                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">{{ __('Machine') }}</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">{{ __('Translated') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-button type="button" variant="soft" size="sm" wire:click="suggest({{ $t->id }})" wire:loading.attr="disabled" wire:target="suggest({{ $t->id }})">{{ __('Suggest') }}</x-button>
                                        <x-button type="button" variant="primary" size="sm" icon="save" wire:click="save({{ $t->id }})" wire:loading.attr="disabled" wire:target="save({{ $t->id }})">{{ __('Save') }}</x-button>
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
</div>
