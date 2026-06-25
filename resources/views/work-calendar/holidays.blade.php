<x-app-layout>
    <x-slot name="header">Holidays &amp; Work Suspensions</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400">No working hours are counted on these dates, for everyone.</p>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-xs text-gray-400">Year</label>
                <select name="year" class="input py-1.5" onchange="this.form.submit()">
                    @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        {{-- Add --}}
        <x-card>
            <form method="POST" action="{{ route('work-calendar.holidays.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                @csrf
                <div class="sm:col-span-3">
                    <label class="label">Date</label>
                    <input type="date" name="date" value="{{ old('date') }}" class="input" required>
                </div>
                <div class="sm:col-span-3">
                    <label class="label">Type</label>
                    <select name="type" class="input" required>
                        <option value="holiday">Holiday</option>
                        <option value="suspension">Work suspension</option>
                    </select>
                </div>
                <div class="sm:col-span-4">
                    <label class="label">Label</label>
                    <input type="text" name="label" value="{{ old('label') }}" class="input" placeholder="e.g. Araw ng Kagitingan" required>
                </div>
                <div class="sm:col-span-2">
                    <x-btn type="submit" class="w-full justify-center">Add</x-btn>
                </div>
            </form>
        </x-card>

        {{-- List grouped by month --}}
        <x-card>
            @forelse($days->groupBy(fn ($d) => $d->date->format('F')) as $month => $rows)
                <div class="mb-4 last:mb-0">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ $month }}</h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($rows as $d)
                            <div class="flex items-center gap-3 py-2.5">
                                <div class="w-12 text-center shrink-0">
                                    <div class="text-lg font-semibold leading-none tabular-nums">{{ $d->date->format('d') }}</div>
                                    <div class="text-[10px] uppercase text-gray-400">{{ $d->date->format('D') }}</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $d->label }}</div>
                                    <x-badge :color="$d->type === 'suspension' ? 'amber' : 'indigo'">{{ $d->typeLabel() }}</x-badge>
                                </div>
                                <form method="POST" action="{{ route('work-calendar.holidays.destroy', $d) }}" data-confirm="Remove “{{ $d->label }}”?">
                                    @csrf @method('DELETE')
                                    <button class="act-del shrink-0">Delete</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-6">No holidays or suspensions recorded for {{ $year }}.</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
