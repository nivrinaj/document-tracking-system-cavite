<div class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    {{-- Hidden inputs for form submission --}}
    <template x-for="id in selected" :key="id">
        <input type="hidden" :name="name" :value="id">
    </template>

    {{-- Trigger --}}
    <button type="button" @click="open = !open"
            class="input w-full text-left flex items-center gap-2 min-h-[42px] flex-wrap py-1.5 pr-8">
        <template x-if="selectedLabels.length === 0">
            <span class="text-gray-400 text-sm" x-text="placeholder"></span>
        </template>
        <template x-for="item in selectedLabels" :key="item.id">
            <span class="inline-flex items-center gap-1 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-medium px-2 py-0.5 rounded-full ring-1 ring-primary-200 dark:ring-primary-700">
                <span x-text="item.label.split(' — ')[0]"></span>
                <button type="button" @click.stop="remove(item.id)" class="hover:text-red-500 transition-colors">&times;</button>
            </span>
        </template>
        <svg class="w-4 h-4 text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak x-transition.opacity
         class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
            <input type="text" x-model="search" placeholder="Search..." @click.stop
                   class="input py-1.5 text-sm w-full" autocomplete="off">
        </div>
        <div class="max-h-52 overflow-y-auto p-1">
            <template x-for="item in filtered" :key="item.id">
                <button type="button" @click.stop="toggle(item.id)"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors"
                        :class="isSelected(item.id) ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                    <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0 transition-colors"
                          :class="isSelected(item.id) ? 'bg-[color:var(--color-primary)] border-[color:var(--color-primary)]' : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="isSelected(item.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span x-text="item.label" class="truncate"></span>
                </button>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-400 text-center">No matches</div>
        </div>
    </div>
</div>
