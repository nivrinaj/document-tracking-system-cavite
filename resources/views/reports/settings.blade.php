<x-app-layout>
    <x-slot name="header">Report Settings</x-slot>

    <div class="space-y-6" x-data="{ rpt: 'erecord' }">
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
                        <input type="text" name="erecord_title" value="{{ old('erecord_title', $title) }}" class="input" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <p class="text-xs text-gray-400 mb-3">Select the offices allowed to use the E-Record. Leave empty to default to offices flagged "Voucher &amp; Payroll office". (Super Admins can always run it.)</p>
                <div x-data="multiSelect({
                    items: @js($departments->map(fn($d) => ['id' => (string)$d->id, 'label' => $d->code.' — '.$d->name])),
                    selected: @js(array_map('strval', $offices)),
                    name: 'erecord_offices[]',
                    placeholder: '— Select offices —',
                })">
                    <x-reports._multi-select />
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
              x-show="rpt === 'transmittal'" x-cloak
              x-data="{
                  selOffices: @js(array_map('strval', $tOffices)),
                  allDivisions: @js($divisions->map(fn($d) => ['id' => (string)$d->id, 'label' => ($d->code ?? $d->name).' — '.$d->name, 'department_id' => (string)$d->department_id])),
                  get filteredDivisions() {
                      if (!this.selOffices.length) return this.allDivisions;
                      return this.allDivisions.filter(d => this.selOffices.includes(d.department_id));
                  }
              }">
            @csrf @method('PUT')
            <input type="hidden" name="_report" value="transmittal">

            <x-card>
                <h2 class="font-semibold text-sm mb-4">Transmittal of Reviewed Disbursement</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Report title</label>
                        <input type="text" name="transmittal_title" value="{{ old('transmittal_title', $tTitle) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">ISO code</label>
                        <input type="text" name="transmittal_iso" value="{{ old('transmittal_iso', $tIso) }}" class="input" placeholder="e.g. PGC ACCTG. R.002">
                    </div>
                    <div>
                        <label class="label">Date source for "Date Received" columns</label>
                        <select name="transmittal_date_source" class="input">
                            <option value="received_by_division" @selected($tDateSource === 'received_by_division')>Date received by the configured division</option>
                            <option value="created" @selected($tDateSource === 'created')>Date encoded / created</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Controls what date appears in the "Date Received" columns.</p>
                    </div>
                </div>
            </x-card>

            {{-- Toggles --}}
            <x-card padding="p-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 px-4 pt-4 pb-2">Options</p>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 mx-4 mb-4">
                    <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">Show page subtotal &amp; grand total</span>
                            <span class="block text-xs text-gray-400">Print a subtotal at the bottom of each page and a grand total on the last page.</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="transmittal_show_totals" value="0">
                            <input type="checkbox" name="transmittal_show_totals" value="1" class="peer sr-only" @checked($tShowTotals)>
                            <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                            <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                    <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">Show page number in footer</span>
                            <span class="block text-xs text-gray-400">Print a centered page number at the bottom of each page.</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="transmittal_page_number" value="0">
                            <input type="checkbox" name="transmittal_page_number" value="1" class="peer sr-only" @checked($tPageNumber)>
                            <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                            <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Offices that may run this report</h2>
                <p class="text-xs text-gray-400 mb-3">Select which office(s) can generate the Transmittal. (Super Admins can always run it.)</p>
                <div x-data="multiSelect({
                    items: @js($departments->map(fn($d) => ['id' => (string)$d->id, 'label' => $d->code.' — '.$d->name])),
                    selected: selOffices,
                    name: 'transmittal_offices[]',
                    placeholder: '— Select offices —',
                    sync: v => selOffices = v,
                })">
                    <x-reports._multi-select />
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Divisions that may run this report</h2>
                <p class="text-xs text-gray-400 mb-3">Staff in these divisions (plus department heads of the selected offices above) can generate. Leave empty to allow any division in the selected offices.</p>
                <div x-data="multiSelect({
                    items: [],
                    selected: @js(array_map('strval', $tDivisions)),
                    name: 'transmittal_divisions[]',
                    placeholder: '— Select divisions —',
                })" x-effect="items = filteredDivisions">
                    <x-reports._multi-select />
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

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('multiSelect', ({ items, selected, name, placeholder, sync }) => ({
            items: items || [],
            selected: [...(selected || [])],
            name: name,
            placeholder: placeholder || '— Select —',
            open: false,
            search: '',
            init() {
                this.$watch('items', () => {
                    const validIds = new Set(this.items.map(i => i.id));
                    this.selected = this.selected.filter(s => validIds.has(s));
                    if (sync) sync(this.selected);
                });
            },
            get filtered() {
                const q = this.search.toLowerCase();
                return this.items.filter(i => !q || i.label.toLowerCase().includes(q));
            },
            get selectedLabels() {
                return this.items.filter(i => this.selected.includes(i.id));
            },
            toggle(id) {
                const idx = this.selected.indexOf(id);
                if (idx >= 0) this.selected.splice(idx, 1);
                else this.selected.push(id);
                if (sync) sync(this.selected);
            },
            remove(id) {
                this.selected = this.selected.filter(v => v !== id);
                if (sync) sync(this.selected);
            },
            isSelected(id) { return this.selected.includes(id); },
        }));
    });
    </script>
</x-app-layout>
