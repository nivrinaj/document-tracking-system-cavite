@props(['prefix', 'rules' => [], 'overdueColor' => '#dc2626'])

<div x-data="{
        rules: @js(collect($rules)->map(fn ($r) => ['days' => $r['days'] ?? 1, 'color' => $r['color'] ?? '#f97316'])->values()),
        overdue: '{{ $overdueColor }}',
        add() { this.rules.push({ days: 1, color: '#f97316' }); },
        remove(i) { this.rules.splice(i, 1); },
     }" class="space-y-3">
    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5">
        <div>
            <span class="block text-sm font-medium">Overdue color</span>
            <span class="block text-xs text-gray-400">Applied once the deadline has passed.</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <input type="color" :name="'{{ $prefix }}_overdue_color'" x-model="overdue" class="w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
            <input type="text" x-model="overdue" class="input w-24 font-mono text-xs py-1.5" maxlength="7">
        </div>
    </div>

    <template x-for="(rule, i) in rules" :key="i">
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <input type="number" x-model.number="rule.days" :name="'{{ $prefix }}_rule_days[' + i + ']'" min="0.5" step="0.5" class="input w-20 py-1.5 text-sm" placeholder="Days">
                <span class="text-xs text-gray-400 whitespace-nowrap">day(s) before deadline</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <input type="color" x-model="rule.color" :name="'{{ $prefix }}_rule_colors[' + i + ']'" class="w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                <input type="text" x-model="rule.color" class="input w-24 font-mono text-xs py-1.5" maxlength="7">
            </div>
            <button type="button" @click="remove(i)" class="w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors shrink-0" title="Remove rule">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    <button type="button" @click="add()" class="inline-flex items-center gap-1.5 text-sm font-medium" style="color: var(--color-primary)">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Add a rule
    </button>
    <p x-show="!rules.length" class="text-xs text-gray-400">No rules — documents will only be marked once overdue.</p>
</div>
