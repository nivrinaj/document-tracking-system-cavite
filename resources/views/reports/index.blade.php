<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    @if(empty($reports))
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">No reports are available for your office yet.</p>
        </x-card>
    @else
    <div x-data="erecordForm('{{ route('reports.erecord') }}')" class="space-y-3">
        @role('Super Admin')
            <div class="flex justify-end">
                <a href="{{ route('reports.settings') }}" class="inline-flex items-center gap-1.5 text-sm link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Report settings
                </a>
            </div>
        @endrole

        <div class="grid grid-cols-1 lg:grid-cols-[340px_minmax(0,1fr)] gap-4 items-start">
            {{-- Left: report + filters --}}
            <x-card>
                <label class="label">Report</label>
                <select x-model="report" class="input">
                    <option value="">— Select report —</option>
                    @foreach($reports as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div x-show="report === 'erecord'" x-cloak class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-sm mb-4">Filters</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="label">Document Type <span class="text-red-500">*</span></label>
                            <select x-model="documentType" class="input">
                                <option value="">— Select document type —</option>
                                @foreach($eDocTypes as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Fund <span class="text-red-500">*</span></label>
                            <select x-model="fundId" class="input">
                                <option value="">— Select fund —</option>
                                @foreach($eFunds as $f)<option value="{{ $f->id }}">{{ $f->name }} ({{ $f->reportCode() }})</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Hospital Division</label>
                            <select x-model="hospital" class="input">
                                <option value="exclude">Exclude hospital transactions</option>
                                <option value="include">Include hospital transactions</option>
                                <option value="only">Hospital transactions only</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Date &amp; time range <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <div class="space-y-2">
                                <input type="datetime-local" x-model="dateFrom" class="input" aria-label="From">
                                <input type="datetime-local" x-model="dateTo" class="input" aria-label="To">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Leave blank for all dates; use one for open-ended.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <x-btn type="button" @click="openPdf()" x-bind:disabled="!ready">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Generate PDF
                        </x-btn>
                        <button type="button" @click="refresh()" x-show="ready" class="text-sm link">↻ Refresh preview</button>
                    </div>
                </div>
            </x-card>

            {{-- Right: live preview --}}
            <x-card padding="p-0" class="overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm font-medium">Preview</span>
                    <span class="text-[11px] text-gray-400">Updates as you change filters · {{ strtoupper(\App\Models\Setting::get('erecord_paper','a4')) }} {{ \App\Models\Setting::get('erecord_orientation','landscape') }}</span>
                </div>
                <div class="relative bg-gray-100 dark:bg-gray-900" style="height: 74vh;">
                    <div x-show="!ready" class="absolute inset-0 grid place-items-center text-sm text-gray-400 px-6 text-center">
                        Pick a report, Document Type and Fund to preview.
                    </div>
                    <iframe x-ref="frame" x-show="ready" class="w-full h-full bg-white" title="Report preview"></iframe>
                </div>
            </x-card>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('erecordForm', (base) => ({
                base,
                report: '',
                documentType: '',
                fundId: '',
                hospital: 'exclude',
                dateFrom: '',
                dateTo: '',
                _t: null,
                get ready() { return this.report === 'erecord' && this.documentType && this.fundId; },
                query(format) {
                    const p = new URLSearchParams({ document_type: this.documentType, fund_id: this.fundId, hospital: this.hospital, format });
                    if (this.dateFrom) p.set('date_from', this.dateFrom);
                    if (this.dateTo) p.set('date_to', this.dateTo);
                    return this.base + '?' + p.toString();
                },
                refresh() { if (this.ready && this.$refs.frame) this.$refs.frame.src = this.query('html'); },
                openPdf() { if (this.ready) window.open(this.query('pdf'), '_blank'); },
                debounced() { clearTimeout(this._t); this._t = setTimeout(() => this.refresh(), 350); },
                init() {
                    ['report', 'documentType', 'fundId', 'hospital', 'dateFrom', 'dateTo'].forEach(k => this.$watch(k, () => this.debounced()));
                },
            }));
        });
    </script>
    @endif
</x-app-layout>
