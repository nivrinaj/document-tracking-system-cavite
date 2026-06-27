<x-app-layout>
    <x-slot name="header">Report Settings</x-slot>

    <div class="max-w-2xl mx-auto space-y-6" x-data="{ rpt: 'erecord' }">
        <a href="{{ route('reports.index') }}" class="text-sm link">&larr; Back to Reports</a>
        <p class="text-sm text-gray-500 dark:text-gray-400">Configure how each report prints and which offices may run it. Staff only generate &mdash; they don't set these.</p>

        <div>
            <label class="label">Report type</label>
            <select x-model="rpt" class="input sm:max-w-sm">
                <option value="erecord">E-Record</option>
                <option value="transmittal">Transmittal of Reviewed Disbursement</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Settings below apply to the selected report.</p>
        </div>

        {{-- ───────── E-Record settings ───────── --}}
        <form method="POST" action="{{ route('reports.settings.save') }}" class="space-y-6"
              x-show="rpt === 'erecord'" x-cloak>
            @csrf @method('PUT')
            <input type="hidden" name="_report" value="erecord">

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
                <p class="text-xs text-gray-400 mb-3">Tick the offices allowed to use the E-Record. Leave all unticked to default to offices flagged "Voucher &amp; Payroll office". (Super Admins can always run it.)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-64 overflow-y-auto">
                    @foreach($departments as $dept)
                        <label class="flex items-center gap-2 text-sm py-1">
                            <input type="checkbox" name="erecord_offices[]" value="{{ $dept->id }}" class="rounded text-[color:var(--color-primary)]" @checked(in_array((string) $dept->id, $offices, true))>
                            <span>{{ $dept->code }} <span class="text-gray-400">&mdash; {{ $dept->name }}</span></span>
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

        {{-- ───────── Transmittal settings ───────── --}}
        <form method="POST" action="{{ route('reports.settings.save') }}" class="space-y-6"
              x-show="rpt === 'transmittal'" x-cloak>
            @csrf @method('PUT')
            <input type="hidden" name="_report" value="transmittal">

            <x-card>
                <h2 class="font-semibold text-sm mb-4">Transmittal of Reviewed Disbursement</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Report title</label>
                        <input type="text" name="transmittal_title" value="{{ old('transmittal_title', $tTitle) }}" class="input sm:max-w-sm" required>
                    </div>
                    <div>
                        <label class="label">ISO code</label>
                        <input type="text" name="transmittal_iso" value="{{ old('transmittal_iso', $tIso) }}" class="input sm:max-w-sm" placeholder="e.g. PGC ACCTG. R.002">
                    </div>
                    <div>
                        <label class="label">Date source for "Date Received" columns</label>
                        <select name="transmittal_date_source" class="input sm:max-w-sm">
                            <option value="received_by_division" @selected($tDateSource === 'received_by_division')>Date received by the configured division</option>
                            <option value="created" @selected($tDateSource === 'created')>Date encoded / created</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Controls what date appears in the "Date Received" columns.</p>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="transmittal_page_number" value="1" class="rounded text-[color:var(--color-primary)]" @checked($tPageNumber)>
                            <span class="text-sm">Show page number in footer</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Offices that may run this report</h2>
                <p class="text-xs text-gray-400 mb-3">Select which office(s) can generate the Transmittal. (Super Admins can always run it.)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-64 overflow-y-auto">
                    @foreach($departments as $dept)
                        <label class="flex items-center gap-2 text-sm py-1">
                            <input type="checkbox" name="transmittal_offices[]" value="{{ $dept->id }}" class="rounded text-[color:var(--color-primary)]" @checked(in_array((string) $dept->id, $tOffices, true))>
                            <span>{{ $dept->code }} <span class="text-gray-400">&mdash; {{ $dept->name }}</span></span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Divisions that may run this report</h2>
                <p class="text-xs text-gray-400 mb-3">Staff in these divisions (plus department heads of the selected offices above) can generate. Leave all unticked to allow any division in the selected offices.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-64 overflow-y-auto">
                    @foreach($divisions as $div)
                        <label class="flex items-center gap-2 text-sm py-1">
                            <input type="checkbox" name="transmittal_divisions[]" value="{{ $div->id }}" class="rounded text-[color:var(--color-primary)]" @checked(in_array((string) $div->id, $tDivisions, true))>
                            <span>{{ $div->code ?? $div->name }} <span class="text-gray-400">&mdash; {{ $div->name }}</span></span>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Column labels &amp; alignment</h2>
                <p class="text-xs text-gray-400 mb-3">Rename and align each column. Leave blank to use the default name.</p>
                <div class="space-y-2">
                    @foreach($tCols as $key => $defaultLabel)
                        <div class="flex items-center gap-3 py-1">
                            <span class="text-sm text-gray-400 w-36 shrink-0 truncate" title="{{ $defaultLabel }}">{{ $defaultLabel }}</span>
                            <input type="text" name="labels[{{ $key }}]" value="{{ ($tLabels[$key] ?? '') !== $defaultLabel ? ($tLabels[$key] ?? '') : '' }}" placeholder="{{ $defaultLabel }}" class="input py-1.5 flex-1 min-w-0">
                            <select name="align[{{ $key }}]" class="input py-1.5 w-[110px] shrink-0">
                                @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $v => $l)
                                    <option value="{{ $v }}" @selected(($tAlign[$key] ?? 'center') === $v)>{{ $l }}</option>
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
