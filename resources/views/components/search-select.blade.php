@props(['name', 'options' => [], 'placeholder' => 'Select…'])

{{-- A searchable single-select. $options: array of ['value','label','group'(optional)].
     Both icons (clear + chevron) live in ONE flex wrapper, absolutely positioned as a
     single unit — flex-with-gap lays them out side by side with zero risk of overlap,
     unlike positioning each icon independently with separate offsets. --}}
<div x-data="{ open: false, q: '', val: '', label: '', opts: @js($options) }" @click.outside="open = false" class="relative">
    <input type="hidden" name="{{ $name }}" :value="val">
    <button type="button" @click="open = !open; q = ''; $nextTick(() => open && $refs.q && $refs.q.focus())"
            class="input-btn text-left pr-14 block">
        <span class="truncate block" :class="!val ? 'text-gray-400' : ''" x-text="val ? label : @js($placeholder)"></span>
    </button>
    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
        <button type="button" x-show="val" x-cloak @click.stop="val = ''; label = ''"
                class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Clear selection">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div x-show="open" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
            <input x-ref="q" type="text" x-model="q" @click.stop class="input py-1.5 text-sm" placeholder="Search…">
        </div>
        <div class="max-h-56 overflow-y-auto py-1 text-sm">
            <template x-for="o in opts.filter(o => { const s = q.toLowerCase().trim(); return !s || o.label.toLowerCase().includes(s) || (o.group || '').toLowerCase().includes(s); })" :key="o.value">
                <button type="button" @click="val = String(o.value); label = o.label; open = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <span x-text="o.label"></span><span class="text-xs text-gray-400" x-show="o.group" x-text="' · ' + o.group"></span>
                </button>
            </template>
            <p x-show="!opts.length" class="px-3 py-2 text-gray-400">No options available.</p>
        </div>
    </div>
</div>
