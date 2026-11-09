@php
    $yesNo = ['yes' => __('Yes'), 'no' => __('No')];
    $item = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
@endphp
<x-page :title="__('Translations')" :crumbs="[[__('General Configurations'), null], [__('Translations'), null]]">
    <x-slot:actions>
        <x-button :href="route('admin.translations.create')" variant="primary" icon="plus">{{ __('Add keyword') }}</x-button>
    </x-slot:actions>

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
                            <x-th class="w-20 text-end last:rounded-se-lg">{{ __('Actions') }}</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $t)
                            <tr wire:key="tr-{{ $t->id }}" class="group transition-colors odd:bg-white even:bg-slate-50/50 hover:bg-indigo-50/60">
                                <td class="border-s-4 border-transparent px-4 py-3 text-xs font-medium text-slate-400 transition group-hover:border-indigo-500">{{ $rows->firstItem() + $loop->index }}</td>
                                <td class="max-w-xs px-4 py-3 text-xs text-slate-600 [overflow-wrap:anywhere]">{{ $t->key }}</td>
                                <td class="max-w-sm px-4 py-3 text-slate-800 [overflow-wrap:anywhere]">{{ $t->en ?: '—' }}</td>
                                <td class="max-w-sm px-4 py-3 text-slate-800 [overflow-wrap:anywhere]" dir="rtl" lang="ar">{{ $t->ar ?: '—' }}</td>
                                <td data-col="type" class="px-4 py-3">
                                    <span class="rounded-md px-2 py-0.5 text-xs font-semibold {{ $t->type === 'keyword' ? 'bg-fuchsia-50 text-fuchsia-700' : 'bg-slate-100 text-slate-700' }}">{{ $t->type === 'keyword' ? __('Keyword') : __('Code') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-row-menu>
                                            <a href="{{ route('admin.translations.edit', $t) }}" wire:navigate role="menuitem" class="{{ $item }} hover:bg-slate-50">
                                                <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                                {{ __('Edit') }}
                                            </a>
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <a href="{{ route('admin.translations.delete', $t) }}" wire:navigate role="menuitem" class="{{ $item }} text-rose-600 hover:bg-rose-50">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                {{ __('Delete') }}
                                            </a>
                                        </x-row-menu>
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