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
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                    <div x-show="type !== 'dept_dayoff'" x-cloak>
                        <label class="label">Staff</label>
                        <select name="user_id" class="input" x-bind:required="type !== 'dept_dayoff'">
                            <option value="">— Select —</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('user_id')==$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="type === 'user_undertime'" x-cloak>
                        <label class="label">Hours actually worked</label>
                        <input type="number" step="0.5" min="0" max="24" name="worked_hours" value="{{ old('worked_hours') }}" class="input" placeholder="e.g. 6" x-bind:required="type === 'user_undertime'">
                    </div>
                    <div :class="type === 'user_undertime' ? '' : 'sm:col-span-2'">
                        <label class="label">Reason <span class="text-red-500">*</span></label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="input" placeholder="e.g. Team building / medical leave" required>
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-btn type="submit">Record</x-btn>
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
</x-app-layout>
