<div class="relative"
     x-data="{
         open: false, mobile: false, hl: -1,
         list() { return [...this.$root.querySelectorAll('[data-result]')]; },
         mark() { this.list().forEach((el, i) => el.classList.toggle('bg-indigo-50', i === this.hl)); },
         move(d) { const n = this.list().length; if (!n) return; this.hl = (this.hl + d + n) % n; this.mark(); this.list()[this.hl].scrollIntoView({ block: 'nearest' }); },
         go() { const el = this.list()[Math.max(this.hl, 0)]; if (el) el.click(); },
         close() { this.open = false; this.mobile = false; this.hl = -1; },
         focus() { this.mobile = true; this.open = true; this.$nextTick(() => this.$refs.q.focus()); },
     }"
     @keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); focus(); }"
     @keydown.escape.window="close()" @click.outside="close()">

    <button type="button" class="rounded-lg bg-white p-2 text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50 md:hidden" @click="focus()" aria-label="{{ __('Search') }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </button>

    <div :class="mobile ? 'fixed inset-x-0 top-0 z-50 bg-white p-3 shadow-lg md:static md:bg-transparent md:p-0 md:shadow-none' : 'hidden md:block'">
        <div class="relative">
            <svg class="pointer-events-none absolute start-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input x-ref="q" type="search" wire:model.live.debounce.250ms="q" autocomplete="off" maxlength="60"
                   placeholder="{{ __('Search tasks, people, pages…') }}" aria-label="{{ __('Search tasks, people, pages…') }}"
                   @focus="open = true" @input="open = true; hl = 0; setTimeout(() => mark(), 450)"
                   @keydown.arrow-down.prevent="open = true; move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
                   class="block w-full rounded-lg border-0 bg-white py-2 ps-9 pe-8 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 md:w-56 lg:w-64 xl:w-72">
            <span wire:loading wire:target="q" class="absolute end-2.5 top-2.5 text-indigo-500">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
            </span>
            <kbd wire:loading.remove wire:target="q" class="pointer-events-none absolute end-2 top-2 hidden rounded border border-slate-200 px-1.5 text-[10px] font-medium text-slate-400 md:block">/</kbd>
        </div>

        <div x-show="open" x-cloak x-transition.opacity.duration.100ms role="listbox"
             class="absolute inset-x-3 mt-1 max-h-96 overflow-y-auto rounded-xl bg-white p-1.5 text-sm shadow-xl ring-1 ring-slate-200 md:inset-x-auto md:end-0 md:w-96">
            @forelse ($groups as $group)
                <p class="px-3 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $group['title'] }}</p>
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" wire:navigate data-result role="option" @click="close()" wire:key="gs-{{ $loop->parent->index }}-{{ $loop->index }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 transition hover:bg-indigo-50">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-slate-900">{{ $item['label'] }}</span>
                            @if ($item['sub'])<span class="block truncate text-xs text-slate-500">{{ $item['sub'] }}</span>@endif
                        </span>
                    </a>
                @endforeach
            @empty
                <p class="px-3 py-6 text-center text-slate-400">{{ __('No results for') }} “{{ $term }}”</p>
            @endforelse
        </div>
    </div>
</div>
