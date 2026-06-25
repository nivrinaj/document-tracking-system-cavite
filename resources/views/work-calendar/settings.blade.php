<x-app-layout>
    <x-slot name="header">Work Hours</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Define the office working schedule. When enabled, the system counts how long a document sits with each
            person using <strong>working hours only</strong> — skipping nights, weekends, lunch, holidays and approved leave.
        </p>

        <form method="POST" action="{{ route('work-calendar.settings.save') }}" class="space-y-6">
            @csrf @method('PUT')

            <x-card>
                <h2 class="font-semibold mb-4 text-sm">Daily schedule</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Work starts</label>
                        <input type="time" name="work_start" value="{{ old('work_start', $cfg['start']) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Work ends</label>
                        <input type="time" name="work_end" value="{{ old('work_end', $cfg['end']) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Lunch starts</label>
                        <input type="time" name="work_lunch_start" value="{{ old('work_lunch_start', $cfg['lunch_start']) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Lunch ends</label>
                        <input type="time" name="work_lunch_end" value="{{ old('work_lunch_end', $cfg['lunch_end']) }}" class="input" required>
                    </div>
                </div>

                <label class="label mt-5 block">Working days</label>
                <div class="flex flex-wrap gap-2">
                    @php $names = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun']; @endphp
                    @foreach($names as $iso => $name)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="work_days[]" value="{{ $iso }}" class="peer sr-only" @checked(in_array($iso, old('work_days', $cfg['days'])))>
                            <span class="inline-flex px-3.5 py-1.5 rounded-lg text-sm border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 peer-checked:text-white peer-checked:border-transparent transition" style="--tw-bg:var(--color-primary)" :class="''">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-400 mt-2">Ticked days are working days. Lunch is excluded automatically.</p>
            </x-card>

            <x-card>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="work_hours_enabled" value="0">
                    <input type="checkbox" name="work_hours_enabled" value="1" class="rounded mt-0.5 text-[color:var(--color-primary)]" @checked(old('work_hours_enabled', $enabled))>
                    <span>
                        <span class="text-sm font-medium">Count pending time in working hours</span>
                        <span class="block text-xs text-gray-400">When off, the system uses plain calendar time (the original behaviour).</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 cursor-pointer mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <input type="hidden" name="show_daily_breakdown" value="0">
                    <input type="checkbox" name="show_daily_breakdown" value="1" class="rounded mt-0.5 text-[color:var(--color-primary)]" @checked(old('show_daily_breakdown', $showBreakdown))>
                    <span>
                        <span class="text-sm font-medium">Show per-day working-hours breakdown on documents</span>
                        <span class="block text-xs text-gray-400">Adds a “Daily working time” panel (e.g. Mon 2h, Tue 8h) to each document detail.</span>
                    </span>
                </label>
            </x-card>

            <x-card>
                <h2 class="font-semibold mb-1 text-sm">Calendar display</h2>
                <p class="text-xs text-gray-400 mb-3">How the Holidays and Department calendars are shown. List and Grid work identically — just different looks.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @php $modes = ['grid' => ['Grid', 'Month calendar view'], 'list' => ['List', 'Compact dated list'], 'disabled' => ['Form only', 'Hide the calendar view']]; @endphp
                    @foreach($modes as $val => [$title, $desc])
                        <label class="cursor-pointer">
                            <input type="radio" name="calendar_display" value="{{ $val }}" class="peer sr-only" @checked(old('calendar_display', $displayMode) === $val)>
                            <span class="block rounded-xl border border-gray-200 dark:border-gray-600 p-3 transition peer-checked:border-[color:var(--color-primary)] peer-checked:ring-2 peer-checked:ring-[color:var(--color-primary)]/30">
                                <span class="block text-sm font-medium">{{ $title }}</span>
                                <span class="block text-xs text-gray-400">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save settings</x-btn>
            </div>
        </form>
    </div>

    <style>
        /* Selected working-day pill uses the theme primary colour. */
        label > input.peer:checked + span { background: var(--color-primary); }
    </style>
</x-app-layout>
