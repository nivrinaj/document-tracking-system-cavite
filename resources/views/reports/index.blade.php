<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    @if(empty($reports))
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">No reports are available for your office yet.</p>
        </x-card>
    @else
    <div x-data="reportPage()" class="space-y-3">
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

                {{-- ── E-Record filters ── --}}
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
                            <label class="label">Date range <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <div class="space-y-2">
                                <input type="date" x-model="dateFrom" class="input" aria-label="From date">
                                <input type="date" x-model="dateTo" class="input" aria-label="To date">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Leave blank for all dates; use one for open-ended.</p>
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" x-model="useTime" class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Include time range</span>
                            </label>
                            <div x-show="useTime" x-cloak class="mt-2 space-y-2">
                                <input type="time" x-model="timeFrom" class="input" aria-label="From time">
                                <input type="time" x-model="timeTo" class="input" aria-label="To time">
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <x-btn type="button" @click="openPdf()" x-bind:disabled="!ready">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Generate PDF
                        </x-btn>
                        <button type="button" @click="refresh()" x-show="ready" class="text-sm link">&circlearrowright; Refresh preview</button>
                    </div>
                </div>

                {{-- ── Transmittal filters ── --}}
                <div x-show="report === 'transmittal'" x-cloak class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-sm mb-4">Filters</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="label">Fund <span class="text-red-500">*</span></label>
                            <select x-model="tFundId" class="input">
                                <option value="">— Select fund —</option>
                                @foreach($tFunds as $f)<option value="{{ $f->id }}">{{ $f->name }} ({{ $f->reportCode() }})</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Hospital Division</label>
                            <select x-model="tHospital" class="input">
                                <option value="exclude">Exclude hospital transactions</option>
                                <option value="include">Include hospital transactions</option>
                                <option value="only">Hospital transactions only</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Date source</label>
                            <select x-model="tDateSource" class="input">
                                <option value="received_by_division">Date received by division</option>
                                <option value="created">Date encoded / created</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Date range <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <div class="space-y-2">
                                <input type="date" x-model="tDateFrom" class="input" aria-label="From date">
                                <input type="date" x-model="tDateTo" class="input" aria-label="To date">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Leave blank for all dates.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <x-btn type="button" @click="openPdf()" x-bind:disabled="!ready">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Generate PDF
                        </x-btn>
                        <button type="button" @click="refresh()" x-show="ready" class="text-sm link">&circlearrowright; Refresh preview</button>
                    </div>
                </div>

                {{-- ── Document Aging Report filters ── --}}
                <div x-show="report === 'doctrack'" x-cloak class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-sm mb-4">Filters</h3>
                    <p class="text-xs text-gray-400 -mt-2 mb-4">All filters are optional — leave blank to include every document in your office.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="label">Document Type</label>
                            <select x-model="dDocType" class="input">
                                <option value="">All document types</option>
                                @foreach($docTypes as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Department</label>
                            <div class="relative" @click.outside="dDeptOpen = false">
                                <button type="button" @click="dDeptOpen = !dDeptOpen; dDeptSearch = ''" class="input-btn text-left pr-14 block">
                                    <span class="truncate block" :class="!dDept ? 'text-gray-400' : ''" x-text="dDeptLabel"></span>
                                </button>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                    <button type="button" x-show="dDept" x-cloak @click.stop="dDept = ''; dDivision = ''; dUser = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="dDeptOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <div x-show="dDeptOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="dDeptSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                                    <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                        <button type="button" @click="dDept = ''; dDivision = ''; dUser = ''; dDeptOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All departments</button>
                                        <template x-for="d in dFilteredDepts" :key="d.id"><button type="button" @click="dDept = String(d.id); dDivision = ''; dUser = ''; dDeptOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="label">Division <span class="text-gray-400 text-xs font-normal">(pick a department first)</span></label>
                            <div class="relative" @click.outside="dDivOpen = false">
                                <button type="button" @click="dDept && (dDivOpen = !dDivOpen); dDivSearch = ''" class="input-btn text-left pr-14 block" :class="!dDept ? 'opacity-50 cursor-not-allowed' : ''" :disabled="!dDept">
                                    <span class="truncate block" :class="!dDivision ? 'text-gray-400' : ''" x-text="!dDept ? 'Select department first' : dDivisionLabel"></span>
                                </button>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                    <button type="button" x-show="dDivision" x-cloak @click.stop="dDivision = ''; dUser = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="dDivOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <div x-show="dDivOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="dDivSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                                    <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                        <button type="button" @click="dDivision = ''; dUser = ''; dDivOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All divisions</button>
                                        <template x-for="d in dFilteredDivisions" :key="d.id"><button type="button" @click="dDivision = String(d.id); dUser = ''; dDivOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                                        <p x-show="!dFilteredDivisions.length" class="px-3 py-2 text-gray-400">No divisions in this department.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="label">Staff / User</label>
                            <div class="relative" @click.outside="dUserOpen = false">
                                <button type="button" @click="dUserOpen = !dUserOpen; dUserSearch = ''" class="input-btn text-left pr-14 block">
                                    <span class="truncate block" :class="!dUser ? 'text-gray-400' : ''" x-text="dUserLabel"></span>
                                </button>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                    <button type="button" x-show="dUser" x-cloak @click.stop="dUser = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="dUserOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <div x-show="dUserOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="dUserSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                                    <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                        <button type="button" @click="dUser = ''; dUserOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All staff</button>
                                        <template x-for="u in dFilteredUsers" :key="u.id"><button type="button" @click="dUser = String(u.id); dUserOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="u.name"></button></template>
                                        <p x-show="!dFilteredUsers.length" class="px-3 py-2 text-gray-400">No staff match.</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1" x-show="dDept || dDivision">Showing staff from the selected department<span x-show="dDivision">/division</span>.</p>
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select x-model="dStatus" class="input">
                                <option value="">Any status</option>
                                <option value="draft">Pending Release</option>
                                <option value="released">Released</option>
                                <option value="received">Received</option>
                                <option value="forwarded">Forwarded</option>
                                <option value="archived">Archived</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Hospital Division</label>
                            <select x-model="dHospital" class="input">
                                <option value="exclude">Exclude hospital transactions</option>
                                <option value="include">Include hospital transactions</option>
                                <option value="only">Hospital transactions only</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Sort by</label>
                            <select x-model="dSort" class="input">
                                <option value="date_encoded">Date Encoded (Oldest First)</option>
                                <option value="idle_desc">Idle Time (Highest First)</option>
                                <option value="oldest">Age (Oldest First)</option>
                                <option value="status">Status</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Date range <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <div class="space-y-2">
                                <input type="date" x-model="dDateFrom" class="input" aria-label="From date">
                                <input type="date" x-model="dDateTo" class="input" aria-label="To date">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Leave blank for all dates; use one for open-ended.</p>
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" x-model="dUseTime" class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Include time range</span>
                            </label>
                            <div x-show="dUseTime" x-cloak class="mt-2 space-y-2">
                                <input type="time" x-model="dTimeFrom" class="input" aria-label="From time">
                                <input type="time" x-model="dTimeTo" class="input" aria-label="To time">
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <x-btn type="button" @click="openPdf()" x-bind:disabled="!ready">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Generate PDF
                        </x-btn>
                        <button type="button" @click="refresh()" x-show="ready" class="text-sm link">&circlearrowright; Refresh preview</button>
                    </div>
                </div>
            </x-card>

            {{-- Right: live preview --}}
            <x-card padding="p-0" class="overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm font-medium">Preview</span>
                    <span class="text-[11px] text-gray-400">Updates as you change filters</span>
                </div>
                <div class="relative bg-gray-100 dark:bg-gray-900" style="height: 74vh;">
                    <div x-show="!ready" class="absolute inset-0 grid place-items-center text-sm text-gray-400 px-6 text-center">
                        Pick a report and its required filters to preview.
                    </div>
                    <iframe x-ref="frame" x-show="ready" class="w-full h-full bg-white" title="Report preview"></iframe>
                </div>
            </x-card>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reportPage', () => ({
                report: '',
                // E-Record
                documentType: '', fundId: '', hospital: 'exclude',
                dateFrom: '', dateTo: '', useTime: false, timeFrom: '', timeTo: '',
                // Transmittal
                tFundId: '', tHospital: 'exclude', tDateSource: '{{ $tDateSource ?? "received_by_division" }}',
                tDateFrom: '', tDateTo: '',
                // Document Aging Report
                dDocType: '',
                dDept: '{{ $docTrackCanViewAll ? "" : ($docTrackOwnDeptId ?? "") }}', dDeptOpen: false, dDeptSearch: '',
                dDivision: '', dDivOpen: false, dDivSearch: '',
                dUser: '', dUserOpen: false, dUserSearch: '',
                dStatus: '', dHospital: 'exclude', dSort: 'date_encoded', dDateFrom: '', dDateTo: '',
                dUseTime: false, dTimeFrom: '', dTimeTo: '',
                dDepts: @js($docTrackDepartments->map(fn($d) => ['id' => $d->id, 'name' => $d->code.' — '.$d->name])),
                dDivisions: @js($docTrackDivisions->map(fn($d) => ['id' => $d->id, 'name' => $d->code.' — '.$d->name, 'department_id' => $d->department_id])),
                dUsers: @js($docTrackStaff->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'department_id' => $u->department_id, 'division_id' => $u->division_id])),
                get dFilteredDepts() { const q = this.dDeptSearch.toLowerCase().trim(); return this.dDepts.filter(d => !q || d.name.toLowerCase().includes(q)); },
                get dDeptLabel() { const d = this.dDepts.find(x => String(x.id) === String(this.dDept)); return d ? d.name : 'All departments'; },
                get dVisibleDivisions() { return this.dDivisions.filter(d => !this.dDept || String(d.department_id) === String(this.dDept)); },
                get dFilteredDivisions() { const q = this.dDivSearch.toLowerCase().trim(); return this.dVisibleDivisions.filter(d => !q || d.name.toLowerCase().includes(q)); },
                get dDivisionLabel() { const d = this.dDivisions.find(x => String(x.id) === String(this.dDivision)); return d ? d.name : 'All divisions'; },
                get dScopedUsers() { return this.dUsers.filter(u => (!this.dDept || String(u.department_id) === String(this.dDept)) && (!this.dDivision || String(u.division_id) === String(this.dDivision))); },
                get dFilteredUsers() { const q = this.dUserSearch.toLowerCase().trim(); return this.dScopedUsers.filter(u => !q || u.name.toLowerCase().includes(q)); },
                get dUserLabel() { const u = this.dUsers.find(x => String(x.id) === String(this.dUser)); return u ? u.name : 'All staff'; },

                _t: null,
                get ready() {
                    if (this.report === 'erecord') return this.documentType && this.fundId;
                    if (this.report === 'transmittal') return !!this.tFundId;
                    if (this.report === 'doctrack') return true;
                    return false;
                },
                query(format) {
                    if (this.report === 'erecord') {
                        const p = new URLSearchParams({ document_type: this.documentType, fund_id: this.fundId, hospital: this.hospital, format });
                        if (this.dateFrom) p.set('date_from', this.useTime && this.timeFrom ? this.dateFrom + ' ' + this.timeFrom : this.dateFrom);
                        if (this.dateTo) p.set('date_to', this.useTime && this.timeTo ? this.dateTo + ' ' + this.timeTo : this.dateTo);
                        return '{{ route("reports.erecord") }}?' + p.toString();
                    }
                    if (this.report === 'transmittal') {
                        const p = new URLSearchParams({ fund_id: this.tFundId, hospital: this.tHospital, date_source: this.tDateSource, format });
                        if (this.tDateFrom) p.set('date_from', this.tDateFrom);
                        if (this.tDateTo) p.set('date_to', this.tDateTo);
                        return '{{ route("reports.transmittal") }}?' + p.toString();
                    }
                    if (this.report === 'doctrack') {
                        const p = new URLSearchParams({ hospital: this.dHospital, sort: this.dSort, format });
                        if (this.dDocType) p.set('document_type', this.dDocType);
                        if (this.dDept) p.set('department_id', this.dDept);
                        if (this.dDivision) p.set('division_id', this.dDivision);
                        if (this.dUser) p.set('user_id', this.dUser);
                        if (this.dStatus) p.set('status', this.dStatus);
                        if (this.dDateFrom) p.set('date_from', this.dUseTime && this.dTimeFrom ? this.dDateFrom + ' ' + this.dTimeFrom : this.dDateFrom);
                        if (this.dDateTo) p.set('date_to', this.dUseTime && this.dTimeTo ? this.dDateTo + ' ' + this.dTimeTo : this.dDateTo);
                        return '{{ route("reports.doctrack") }}?' + p.toString();
                    }
                    return '';
                },
                refresh() { if (this.ready && this.$refs.frame) this.$refs.frame.src = this.query('html'); },
                openPdf() { if (this.ready) window.open(this.query('pdf'), '_blank'); },
                debounced() { clearTimeout(this._t); this._t = setTimeout(() => this.refresh(), 350); },
                init() {
                    ['report','documentType','fundId','hospital','dateFrom','dateTo','useTime','timeFrom','timeTo',
                     'tFundId','tHospital','tDateSource','tDateFrom','tDateTo',
                     'dDocType','dDept','dDivision','dUser','dStatus','dHospital','dSort','dDateFrom','dDateTo','dUseTime','dTimeFrom','dTimeTo'].forEach(k => this.$watch(k, () => this.debounced()));
                },
            }));
        });
    </script>
    @endif
</x-app-layout>
