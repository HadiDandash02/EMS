{{--
    "⋮" menu for a table row. Put the items in the slot, e.g.
    <button type="button" class="{{ $item }}" wire:click="edit(1)">…</button>
    The panel is position:fixed so the table never clips it; it flips upwards near the bottom edge.
--}}
<div class="relative inline-block text-start"
     x-data="{
         open: false, top: 0, side: 0,
         toggle(e) {
             if (this.open) { this.open = false; return; }
             const r = e.currentTarget.getBoundingClientRect(), rtl = document.dir === 'rtl';
             this.top = r.bottom + 6;
             this.side = rtl ? r.left : window.innerWidth - r.right;
             this.open = true;
             this.$nextTick(() => {
                 const h = this.$refs.menu.offsetHeight;
                 if (r.bottom + h + 12 > window.innerHeight) this.top = Math.max(8, r.top - h - 6);
             });
         },
     }"
     @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false" @click.outside="open = false">
    <button type="button" @click="toggle($event)" aria-haspopup="menu" :aria-expanded="open" aria-label="{{ __('Actions') }}" title="{{ __('Actions') }}"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="open ? 'bg-slate-100 text-slate-900' : ''">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
    </button>

    <div x-ref="menu" x-show="open" x-cloak x-transition.opacity.duration.100ms role="menu" @click="open = false"
         class="select-scroll fixed z-50 max-h-[70vh] w-52 overflow-y-auto overscroll-contain rounded-xl bg-white p-1.5 text-slate-700 shadow-xl ring-1 ring-slate-200"
         :style="`top:${top}px; ${document.dir === 'rtl' ? 'left' : 'right'}:${side}px`">
        {{ $slot }}
    </div>
</div>
