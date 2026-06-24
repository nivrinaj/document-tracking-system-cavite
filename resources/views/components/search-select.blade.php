@props(['name', 'options' => [], 'placeholder' => 'Select…'])

{{-- A searchable single-select. $options: array of ['value','label','group'(optional)]. --}}
<div x-data="{ open: false, q: '', val: '', label: '', opts: @js($options) }" @click.outside="open = false" class="relative">
    <input type="hidden" name="{{ $name }}" :value="val">
    <button type="button" @click="open = !open; q = ''; $nextTick(() => open && $refs.q && $refs.q.focus())"
            class="input-btn flex items-center justify-between text-left">
        <span class="truncate" :class="!val ? 'text-gray-400' : ''" x-text="val ? label : @js($placeholder)"></span>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
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
