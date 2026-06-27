<x-app-layout>
    <x-slot name="header">Report Settings</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <a href="{{ route('reports.index') }}" class="text-sm link">← Back to Reports</a>
        <p class="text-sm text-gray-500 dark:text-gray-400">Configure how each report prints and which offices may run it. Staff only generate — they don't set these.</p>

        <div>
            <label class="label">Report type</label>
            <select x-data x-on:change="$dispatch('report-type', $event.target.value)" class="input sm:max-w-sm">
                <option value="erecord">E-Record</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Settings below apply to the selected report.</p>
        </div>

        {{-- ───────── E-Record settings ───────── --}}
        <form method="POST" action="{{ route('reports.settings.save') }}" class="space-y-6"
              x-data="{ rpt: 'erecord' }" x-on:report-type.window="rpt = $event.detail" x-show="rpt === 'erecord'">
            @csrf @method('PUT')

            <x-card>
                <h2 class="font-semibold text-sm mb-4">E-Record</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Report title</label>
                        <input type="text" name="erecord_title" value="{{ old('erecord_title', $title) }}" class="input sm:max-w-sm" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-md">
                        <div>
                            <label class="label">Paper size</label>
                            <select name="erecord_paper" class="input">
                                @foreach(['a4' => 'A4', 'letter' => 'Letter', 'legal' => 'Legal'] as $v => $l)
                                    <option value="{{ $v }}" @selected(old('erecord_paper', $paper) === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Orientation</label>
                            <select name="erecord_orientation" class="input">
                                @foreach(['landscape' => 'Landscape', 'portrait' => 'Portrait'] as $v => $l)
                                    <option value="{{ $v }}" @selected(old('erecord_orientation', $orientation) === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Offices that may run this report</h2>
                <p class="text-xs text-gray-400 mb-3">Tick the offices allowed to use the E-Record. Leave all unticked to default to offices flagged “Voucher &amp; Payroll office”. (Super Admins can always run it.)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-64 overflow-y-auto">
                    @foreach($departments as $dept)
                        <label class="flex items-center gap-2 text-sm py-1">
                            <input type="checkbox" name="erecord_offices[]" value="{{ $dept->id }}" class="rounded text-[color:var(--color-primary)]" @checked(in_array((string) $dept->id, $offices, true))>
                            <span>{{ $dept->code }} <span class="text-gray-400">— {{ $dept->name }}</span></span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Column labels &amp; alignment</h2>
                <p class="text-xs text-gray-400 mb-3">Rename and align each column on the E-Record. Leave blank to use the default name.</p>
                <div class="space-y-2">
                    @foreach($cols as $key => $defaultLabel)
                        <div class="flex items-center gap-3 py-1">
                            <span class="text-sm text-gray-400 w-28 shrink-0 truncate" title="{{ $defaultLabel }}">{{ $defaultLabel }}</span>
                            <input type="text" name="labels[{{ $key }}]" value="{{ ($labels[$key] ?? '') !== $defaultLabel ? ($labels[$key] ?? '') : '' }}" placeholder="{{ $defaultLabel }}" class="input py-1.5 flex-1 min-w-0">
                            <select name="align[{{ $key }}]" class="input py-1.5 w-[110px] shrink-0">
                                @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $v => $l)
                                    <option value="{{ $v }}" @selected(($align[$key] ?? 'left') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save report settings</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
