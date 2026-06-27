<div class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    {{-- Hidden inputs for form submission --}}
    <template x-for="id in selected" :key="id">
        <input type="hidden" :name="name" :value="id">
    </template>

    {{-- Trigger --}}
    <button type="button" @click="open = !open"
            class="input-btn w-full text-left flex items-center min-h-[42px] pr-8 relative">
        <template x-if="selectedLabels.length === 0">
            <span class="text-gray-400 text-sm" x-text="placeholder"></span>
        </template>
        <template x-if="selectedLabels.length > 0">
            <span class="flex items-center gap-1.5 flex-wrap py-0.5">
                <template x-for="item in selectedLabels" :key="item.id">
                    <span class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold pl-2.5 pr-1 py-1 rounded-lg ring-1 ring-indigo-200 dark:ring-indigo-500/40">
                        <span x-text="item.label.split(' — ')[0]"></span>
                        <button type="button" @click.stop="remove(item.id)"
                                class="w-4 h-4 ml-0.5 inline-flex items-center justify-center rounded hover:bg-indigo-200 dark:hover:bg-indigo-400/30 transition-colors text-indigo-400 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-200 text-xs leading-none">&times;</button>
                    </span>
                </template>
            </span>
        </template>
        <svg class="w-4 h-4 text-gray-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
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
                        :class="isSelected(item.id) ? 'bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                    <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0 transition-colors"
                          :class="isSelected(item.id) ? 'bg-indigo-600 dark:bg-indigo-500 border-indigo-600 dark:border-indigo-500' : 'border-gray-300 dark:border-gray-600'">
                        <svg x-show="isSelected(item.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span x-text="item.label" class="truncate text-left"></span>
                </button>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-400 text-center">No matches</div>
        </div>
    </div>
</div>
