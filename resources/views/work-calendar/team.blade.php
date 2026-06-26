<x-app-layout>
    <x-slot name="header">Department Work Calendar</x-slot>

    @php
        $palette = [
            'dept_dayoff' => ['#eff6ff', '#1d4ed8'],
            'user_leave' => ['#fff1f2', '#be123c'],
            'user_undertime' => ['#f5f3ff', '#6d28d9'],
        ];
        $events = [];
        foreach ($days as $d) {
            [$bg, $fg] = $palette[$d->type] ?? ['#f3f4f6', '#374151'];
            $events[$d->date->format('Y-m-d')][] = [
                'text' => $d->typeLabel().($d->user ? ' · '.$d->user->name : ''),
                'bg' => $bg, 'fg' => $fg,
            ];
        }
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                Record non-working time for <strong>{{ $department->code }} — {{ $department->name }}</strong>.
                Backdating is fine — past leave/undertime is removed from the clock retroactively. Each entry needs a reason and is logged.
            </p>
            <form method="GET" class="flex items-center gap-2 shrink-0">
                @if($canChoose)
                    <select name="department_id" class="input py-1.5" onchange="this.form.submit()">
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}" @selected($dep->id === $department->id)>{{ $dep->code }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="year" class="input py-1.5" onchange="this.form.submit()">
                    @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        {{-- Add --}}
        <x-card>
            <form method="POST" action="{{ route('work-calendar.team.store') }}" class="space-y-4"
                  x-data="{ type: '{{ old('type', 'dept_dayoff') }}' }">
                @csrf
                @if($canChoose)<input type="hidden" name="department_id" value="{{ $department->id }}">@endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">What</label>
                        <select name="type" x-model="type" class="input" required>
                            <option value="dept_dayoff">Department day-off</option>
                            <option value="user_leave">Staff on leave</option>
                            <option value="user_undertime">Staff undertime</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input type="date" name="date" value="{{ old('date') }}" class="input" required
                               x-on:cal-pick.window="$el.value = $event.detail">
                    </div>

                    {{-- Staff slides in for leave / undertime — searchable, grouped by division --}}
                    <div x-show="type !== 'dept_dayoff'" x-cloak x-transition.opacity
                         :class="type === 'user_undertime' ? '' : 'sm:col-span-2'"
                         x-data="staffPicker(@js($staffGroups), '{{ old('user_id') }}')">
                        <label class="label">Staff</label>
                        <input type="hidden" name="user_id" :value="selected" x-bind:required="type !== 'dept_dayoff'">
                        <div class="relative" @click.outside="open=false">
                            <button type="button" @click="open=!open" class="input-btn flex items-center justify-between text-left">
                                <span :class="selected ? '' : 'text-gray-400'" x-text="selectedLabel || '— Select staff —'"></span>
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-72 overflow-auto">
                                <div class="sticky top-0 bg-white dark:bg-gray-800 p-2 border-b border-gray-100 dark:border-gray-700">
                                    <input type="text" x-model="q" @click.stop placeholder="Search staff…" class="input py-1.5 text-sm">
                                </div>
                                <template x-for="g in groups" :key="g.label">
                                    <div x-show="g.items.some(i => match(i))">
                                        <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-700/40" x-text="g.label"></div>
                                        <template x-for="i in g.items.filter(match)" :key="i.id">
                                            <button type="button" @click="pick(i)"
                                                    class="block w-full text-left px-3 py-1.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    :class="String(i.id) === String(selected) ? 'text-[color:var(--color-primary)] font-medium' : ''"
                                                    x-text="i.name"></button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="!groups.some(g => g.items.some(i => match(i)))" class="px-3 py-3 text-sm text-gray-400">No staff found.</div>
                            </div>
                        </div>
                    </div>
                    <div x-show="type === 'user_undertime'" x-cloak x-transition.opacity>
                        <label class="label">Hours actually worked</label>
                        <input type="number" step="0.5" min="0" max="24" name="worked_hours" value="{{ old('worked_hours') }}" class="input" placeholder="e.g. 6" x-bind:required="type === 'user_undertime'">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Reason <span class="text-red-500">*</span></label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="input" placeholder="e.g. Team building / medical leave" required>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <x-btn type="submit">Record</x-btn>
                    </div>
                </div>
            </form>
        </x-card>

        @if($displayMode === 'grid')
            <x-card>@include('work-calendar._grid', ['events' => $events, 'year' => $year])</x-card>
        @endif

        {{-- Entries list (shown in list mode, or as the manage list in grid mode) --}}
        @if($displayMode !== 'disabled')
            <x-card>
                @forelse($days->groupBy(fn ($d) => $d->date->format('F')) as $month => $rows)
                    <div class="mb-4 last:mb-0">
                        <h3 class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ $month }}</h3>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($rows as $d)
                                <div class="flex items-start gap-3 py-2.5">
                                    <div class="w-12 text-center shrink-0">
                                        <div class="text-lg font-semibold leading-none tabular-nums">{{ $d->date->format('d') }}</div>
                                        <div class="text-[10px] uppercase text-gray-400">{{ $d->date->format('D') }}</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium">
                                            {{ $d->typeLabel() }}@if($d->user) · {{ $d->user->name }}@endif
                                            @if($d->worked_hours !== null)<span class="text-gray-400 font-normal">({{ rtrim(rtrim(number_format($d->worked_hours, 2), '0'), '.') }}h worked)</span>@endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $d->reason }}</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">set by {{ $d->creator?->name ?? '—' }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('work-calendar.team.destroy', $d) }}" data-confirm="Remove this entry?">
                                        @csrf @method('DELETE')
                                        <button class="act-del shrink-0">Delete</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">No leave, undertime or day-offs recorded for {{ $year }}.</p>
                @endforelse
            </x-card>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__staffPickerRegistered) return;
            window.__staffPickerRegistered = true;
            Alpine.data('staffPicker', (groups, initial) => ({
                groups: groups || [],
                open: false,
                q: '',
                selected: initial || '',
                get selectedLabel() {
                    for (const g of this.groups) {
                        const f = g.items.find(i => String(i.id) === String(this.selected));
                        if (f) return f.name;
                    }
                    return '';
                },
                match(i) { return ! this.q || i.name.toLowerCase().includes(this.q.toLowerCase()); },
                pick(i) { this.selected = String(i.id); this.open = false; this.q = ''; },
            }));
        });
    </script>
</x-app-layout>
