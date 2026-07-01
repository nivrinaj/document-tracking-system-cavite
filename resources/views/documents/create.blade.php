<x-app-layout>
    <x-slot name="header">Encode New Document</x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('documents.store') }}" class="space-y-6"
              x-data="{
                  docType: '{{ old('document_type', '') }}',
                  voucherNo: '{{ old('voucher_number') }}',
                  voucherTypes: @js($voucherTypeNames),
                  deadlineTypes: @js($deadlineTypeNames),
                  officeDeadline: {{ ($officeDeadline ?? false) ? 'true' : 'false' }},
                  todayStr: '{{ now()->toDateString() }}',
                  get showDeadline() { return this.officeDeadline && this.deadlineTypes.includes(this.docType); },
                  isAccounting: {{ ($isAccounting ?? false) ? 'true' : 'false' }},
                  get acct() { return this.isAccounting && (this.docType === 'Voucher' || this.docType === 'Payroll'); },
                  srcOffice: '{{ old('source_department_id') }}',
                  srcDiv: '{{ old('source_division_id') }}',
                  scope: '{{ old('broadcast_scope', 'none') }}',
                  cross: {{ $crossDept ? 'true' : 'false' }},
                  ownDept: '{{ $ownDeptId }}',
                  office: '{{ old('assignee_office', $crossDept ? '' : $ownDeptId) }}',
                  div: '',
                  recipientSearch: '',
                  recipients: [],
                  routeItems: [''],
                  srcOfficeOpen: false, srcOfficeSearch: '',
                  assigneeId: '{{ old('assignee_id') }}', assigneeOpen: false, assigneeSearch: '',
                  divisions: @js($divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->code.' — '.$d->name, 'department_id' => $d->department_id])),
                  users: @js($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'department_id' => $u->department_id, 'division_id' => $u->division_id, 'division' => $u->division?->code ?? 'Head'])),
                  allUsers: @js($allUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'office' => $u->department?->code ?? '—', 'division' => $u->division?->code ?? 'Head'])),
                  offices: @js($departments->map(fn($d) => ['id' => $d->id, 'label' => $d->code.' — '.$d->name, 'mine' => $d->id == $ownDeptId])),
                  get srcDivs() { return this.divisions.filter(d => this.srcOffice && String(d.department_id) === String(this.srcOffice)); },
                  get ownDivs() { return this.divisions.filter(d => String(d.department_id) === String(this.ownDept)); },
                  get ownStaff() { return this.users.filter(u => !this.div || String(u.division_id) === String(this.div)); },
                  get filteredRecipients() { const q = this.recipientSearch.toLowerCase().trim(); return this.allUsers.filter(u => !q || u.name.toLowerCase().includes(q) || u.office.toLowerCase().includes(q)); },
                  toggleRecipient(id) { const i = this.recipients.indexOf(id); if (i === -1) this.recipients.push(id); else this.recipients.splice(i, 1); },
                  get filteredOffices() { const q = this.srcOfficeSearch.toLowerCase().trim(); return this.offices.filter(o => !q || o.label.toLowerCase().includes(q)); },
                  get srcOfficeLabel() { if (this.srcOffice === '') return '— Select office —'; if (this.srcOffice === 'external') return 'Other / External client'; const o = this.offices.find(x => String(x.id) === String(this.srcOffice)); return o ? o.label + (o.mine ? ' (mine)' : '') : '— Select office —'; },
                  get filteredStaff() { const q = this.assigneeSearch.toLowerCase().trim(); return this.ownStaff.filter(u => !q || u.name.toLowerCase().includes(q)); },
                  get assigneeLabel() { if (!this.assigneeId) return '— Assign later —'; const u = this.users.find(x => String(x.id) === String(this.assigneeId)); return u ? u.name + ' — ' + u.division : '— Assign later —'; },
              }">
            @csrf

            {{-- ───── Document details ───── --}}
            <x-card padding="p-5">
                <div class="flex items-start gap-3 mb-5">
                    <span class="shrink-0 w-8 h-8 rounded-full grid place-items-center text-white text-sm font-semibold" style="background: var(--color-primary)">1</span>
                    <div>
                        <h2 class="font-semibold text-sm">Document details</h2>
                        <p class="text-xs text-gray-400">What is this document?</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Document Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" class="input" required autofocus>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" x-model="docType" class="input" required>
                            <option value="" disabled>— Select document type —</option>
                            @foreach($documentTypes as $t)
                                <option value="{{ $t->name }}" @selected(old('document_type')===$t->name)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2" x-show="!isAccounting && voucherTypes.includes(docType)" x-cloak x-data="{ confirmNo: '{{ old('voucher_number_confirmation') }}' }">
                        <label class="label">Voucher Number <span class="text-red-500">*</span></label>
                        <input type="text" name="voucher_number" x-model="voucherNo" class="input" placeholder="e.g. DV-00123" x-bind:required="!isAccounting && voucherTypes.includes(docType)" autocomplete="off">
                        <p class="text-xs text-gray-400 mt-1">Code: <span class="font-mono">{{ \App\Models\Document::trackingPrefix() }}-{{ date('Y') }}-<span x-text="(voucherNo || 'XXXX').toUpperCase().replace(/[^A-Z0-9\-]/g,'')"></span></span></p>

                        <label class="label mt-3">Confirm Voucher Number <span class="text-red-500">*</span></label>
                        <input type="text" name="voucher_number_confirmation" x-model="confirmNo" class="input" placeholder="Re-type to confirm" x-bind:required="!isAccounting && voucherTypes.includes(docType)" autocomplete="off"
                               onpaste="return false;">
                        <p class="text-xs mt-1" x-show="confirmNo.length > 0">
                            <span x-show="confirmNo === voucherNo" class="text-green-600 dark:text-green-400">✓ Matches — double-check it against the physical voucher.</span>
                            <span x-show="confirmNo !== voucherNo" class="text-red-600 dark:text-red-400">✗ Doesn't match the voucher number above.</span>
                        </p>
                    </div>

                    {{-- ── Accounting details (Voucher & Payroll) ── --}}
                    <div class="sm:col-span-2" x-show="acct" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800/40 dark:to-gray-800/10 p-4 sm:p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="grid place-items-center h-6 w-6 rounded-lg text-white text-xs" style="background: var(--color-primary)">₱</span>
                                <h3 class="text-xs font-semibold text-gray-700 dark:text-gray-200">Accounting details</h3>
                                <span class="ml-auto text-[11px] px-2.5 py-0.5 rounded-full font-semibold tracking-wide bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300 ring-1 ring-indigo-200 dark:ring-indigo-700/50" x-text="docType"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                                {{-- Amount — hero field, full width --}}
                                <div class="sm:col-span-2" x-data="{
                                        raw: @js((string) old('amount', '')),
                                        display: '',
                                        init() { this.display = this.fmt(this.raw); },
                                        fmt(v) {
                                            if (v === '' || v === null) return '';
                                            const parts = String(v).replace(/[^0-9.]/g, '').split('.');
                                            const intp = (parts[0] || '').replace(/^0+(?=\d)/, '');
                                            const dec = parts.length > 1 ? '.' + parts[1].slice(0, 2) : '';
                                            return (intp ? Number(intp).toLocaleString('en-US') : (dec ? '0' : '')) + dec;
                                        },
                                        onInput(e) {
                                            let s = e.target.value.replace(/[^0-9.]/g, '');
                                            const p = s.split('.');
                                            if (p.length > 2) s = p[0] + '.' + p.slice(1).join('');
                                            const [i, d] = s.split('.');
                                            this.raw = (i || '') + (d !== undefined ? '.' + d.slice(0, 2) : '');
                                            this.display = this.fmt(this.raw);
                                        }
                                     }">
                                    <label class="label">Amount <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none">₱</span>
                                        <input type="text" inputmode="decimal" placeholder="0.00" autocomplete="off"
                                               :value="display" @input="onInput($event)" x-bind:required="acct"
                                               class="input pl-8 text-lg font-semibold tabular-nums">
                                    </div>
                                    <input type="hidden" name="amount" :value="raw">
                                </div>

                                <div x-data="{ fundCode: @js(optional($funds->firstWhere('id', (int) old('fund_id')))->code ?? '') }">
                                    <label class="label">Fund <span class="text-red-500">*</span></label>
                                    <select name="fund_id" class="input" x-bind:required="acct"
                                            @change="fundCode = $event.target.selectedOptions[0]?.dataset.code || ''">
                                        <option value="">— Select fund —</option>
                                        @foreach($funds as $f)
                                            <option value="{{ $f->id }}" data-code="{{ $f->code }}" @selected(old('fund_id')==$f->id)>{{ $f->name }} ({{ $f->code }})</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Preview: <span class="font-mono"><span x-text="fundCode || '[fund]'"></span>-{{ date('Y') }}-{{ date('m') }}-N{{ $isHospital ? '-H' : '' }}</span>
                                        <span class="text-gray-300 dark:text-gray-600">· N = running number assigned on save</span>
                                    </p>
                                </div>

                                <div>
                                    <label class="label">OBR No. <span class="text-red-500">*</span></label>
                                    <input type="text" name="obr_no" value="{{ old('obr_no') }}" class="input" placeholder="OBR number, or N/A" x-bind:required="acct">
                                </div>

                                <div class="sm:col-span-2">
                                    <x-rc-picker :is-hospital="$isHospital" :office-options="$rcOfficeOptions" :projects-by-office="$rcProjectsByOffice" :hospital-options="$rcHospitalOptions" :hospital-required="$rcHospitalRequired" />
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="label">Nature of Transaction <span class="text-red-500">*</span></label>
                                    <select name="nature_of_transaction" class="input" x-bind:required="acct">
                                        <option value="">— Select —</option>
                                        @foreach($natures as $n)
                                            <option value="{{ $n->name }}" @selected(old('nature_of_transaction')===$n->name)>{{ $n->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($priorityEnabled)
                    <div>
                        <label class="label">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" class="input" required>
                            @foreach(['normal','low','high','urgent'] as $p)
                                <option value="{{ $p }}" @selected(old('priority','normal')===$p)>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div x-show="showDeadline" x-cloak>
                        <label class="label">Deadline <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                        <input type="date" name="deadline" value="{{ old('deadline') }}" class="input" :min="todayStr" x-bind:disabled="!showDeadline">
                        <p class="text-[11px] text-gray-400 mt-1">Due date from today onwards. The tracking list warns as it nears (orange within 16 working hours, red within 8).</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Description</label>
                        <textarea name="description" rows="3" class="input" placeholder="Short description of the document…">{{ old('description') }}</textarea>
                    </div>
                </div>
            </x-card>

            {{-- ───── Route slip items (optional, when enabled) ───── --}}
            @if(\App\Models\Document::routeItemsEnabled())
            <x-card padding="p-5">
                <div class="flex items-start gap-3 mb-4">
                    <span class="shrink-0 w-8 h-8 rounded-full grid place-items-center text-white text-sm font-semibold" style="background: var(--color-primary)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-sm">Route slip items <span class="text-gray-400 font-normal">(optional)</span></h2>
                        <p class="text-xs text-gray-400">List the individual documents carried by this slip. Each can later be cleared or rejected on its own.</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <template x-for="(it, idx) in routeItems" :key="idx">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5 text-right" x-text="(idx+1)+'.'"></span>
                            <input type="text" name="items[]" x-model="routeItems[idx]" class="input" placeholder="e.g. Disbursement Voucher #123">
                            <button type="button" @click="routeItems.splice(idx,1)" x-show="routeItems.length > 1" class="shrink-0 p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="Remove">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="routeItems.push('')" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium" style="color: var(--color-primary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add another document
                </button>
            </x-card>
            @endif

            {{-- ───── Source / Origin (not needed when transferring out — your office is the origin) ───── --}}
            <div x-show="scope !== 'transfer'" x-cloak>
            <x-card padding="p-5">
                <div class="flex items-start gap-3 mb-5">
                    <span class="shrink-0 w-8 h-8 rounded-full grid place-items-center text-white text-sm font-semibold" style="background: var(--color-primary)">2</span>
                    <div>
                        <h2 class="font-semibold text-sm">Source / Origin</h2>
                        <p class="text-xs text-gray-400">Where did it come from?</p>
                    </div>
                </div>
                @if($crossDept)
                    <div class="flex items-start gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 px-3 py-2.5 mb-4 text-xs text-blue-700 dark:text-blue-300">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Fill this in only for documents handled <strong>within your own office</strong>. Sending it to <strong>another office</strong>? Skip this — go to <strong>Distribution</strong> below and choose <strong>“Transfer to another office.”</strong></span>
                    </div>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Office</label>
                        <div class="relative" @click.outside="srcOfficeOpen = false">
                            <input type="hidden" name="source_department_id" :value="srcOffice">
                            <button type="button" @click="srcOfficeOpen = !srcOfficeOpen; srcOfficeSearch = ''"
                                    class="input-btn flex items-center justify-between text-left">
                                <span class="truncate" :class="srcOffice === '' ? 'text-gray-400' : ''" x-text="srcOfficeLabel"></span>
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="srcOfficeOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                    <input type="text" x-model="srcOfficeSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search office…">
                                </div>
                                <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                    <button type="button" @click="srcOffice = ''; srcDiv = ''; srcOfficeOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">— Select office —</button>
                                    <template x-for="o in filteredOffices" :key="o.id">
                                        <button type="button" @click="srcOffice = String(o.id); srcDiv = ''; srcOfficeOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="o.label + (o.mine ? ' (mine)' : '')"></button>
                                    </template>
                                    <button type="button" @click="srcOffice = 'external'; srcDiv = ''; srcOfficeOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">Other / External client</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <template x-if="srcOffice === 'external'">
                            <div>
                                <label class="label">External source</label>
                                <input type="text" name="source_other" value="{{ old('source_other') }}" class="input" placeholder="Name of external client / office">
                            </div>
                        </template>
                        <template x-if="srcOffice !== 'external'">
                            <div>
                                <label class="label">Division <span class="text-gray-400 text-xs">(optional)</span></label>
                                <select name="source_division_id" x-model="srcDiv" class="input" x-bind:disabled="!srcOffice">
                                    <option value="" x-text="srcOffice ? '— Any / not specified —' : 'Select an office first'"></option>
                                    <template x-for="d in srcDivs" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </x-card>
            </div>

            {{-- ───── Distribution ───── --}}
            <x-card padding="p-5">
                <div class="flex items-start gap-3 mb-5">
                    <span class="shrink-0 w-8 h-8 rounded-full grid place-items-center text-white text-sm font-semibold" style="background: var(--color-primary)">3</span>
                    <div>
                        <h2 class="font-semibold text-sm">Distribution</h2>
                        <p class="text-xs text-gray-400">Where is it going?</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="label">Send as</label>
                    <select name="broadcast_scope" x-model="scope" @change="div=''" class="input">
                        <option value="none">👤 Assign to a staff in my office</option>
                        @if($crossDept)
                            <option value="transfer">📤 Transfer to another office (their receiving staff will claim &amp; assign)</option>
                        @endif
                        <option value="division">📣 Division memo — everyone in my division</option>
                        <option value="department">📣 Department memo — everyone in my department</option>
                        <option value="multi">👥 Send to selected people (within my office)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="scope === 'division' || scope === 'department' || scope === 'multi'" x-cloak>Every recipient is notified and acknowledges receipt individually.</p>
                </div>

                {{-- Assign to a staff in my own office --}}
                <div x-show="scope === 'none'" x-cloak class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <p class="text-xs text-gray-400 mb-3">Assign to someone in <strong>your office</strong>, or leave blank to assign later.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Division</label>
                            <select x-model="div" @change="assigneeId = ''" class="input">
                                <option value="">All divisions</option>
                                <template x-for="d in ownDivs" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="label">Assignee</label>
                            <div class="relative" @click.outside="assigneeOpen = false">
                                <input type="hidden" name="assignee_id" :value="assigneeId">
                                <button type="button" @click="assigneeOpen = !assigneeOpen; assigneeSearch = ''" class="input-btn flex items-center justify-between text-left">
                                    <span class="truncate" :class="!assigneeId ? 'text-gray-400' : ''" x-text="assigneeLabel"></span>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="assigneeOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                        <input type="text" x-model="assigneeSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search staff…">
                                    </div>
                                    <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                        <button type="button" @click="assigneeId = ''; assigneeOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">— Assign later —</button>
                                        <template x-for="u in filteredStaff" :key="u.id">
                                            <button type="button" @click="assigneeId = String(u.id); assigneeOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="u.name + ' — ' + u.division"></button>
                                        </template>
                                        <p x-show="!filteredStaff.length" class="px-3 py-2 text-gray-400">No staff match.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transfer to another office's receiving pool --}}
                @if($crossDept)
                    <div x-show="scope === 'transfer'" x-cloak class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <p class="text-xs text-gray-400 mb-3">Goes to the office's <strong>receiving pool</strong> — no specific person. Their receiving staff claims it, then assigns the exact staff inside their office.</p>
                        <label class="label">Destination office</label>
                        <select name="to_department_id" class="input" x-bind:required="scope === 'transfer'">
                            <option value="">— Select office —</option>
                            @foreach($departments as $dept)
                                @if($dept->id != $ownDeptId)
                                    <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Send to selected people (across offices) --}}
                <div x-show="scope === 'multi'" x-cloak class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <p class="text-xs text-gray-400 mb-3">Pick one or more people in <strong>your office</strong> (across its divisions). Each is notified and acknowledges receipt individually, just like a memo. You can track who has received it.</p>

                    {{-- selected chips --}}
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="recipients.length">
                        <template x-for="id in recipients" :key="id">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-[color:var(--color-primary)]/10 dark:bg-[color:var(--color-primary)]/25 text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)] text-xs">
                                <span x-text="(allUsers.find(u => u.id === id) || {}).name"></span>
                                <button type="button" @click="toggleRecipient(id)" class="hover:opacity-70">&times;</button>
                            </span>
                        </template>
                    </div>

                    <input type="text" x-model="recipientSearch" class="input mb-2" placeholder="Search by name or office…">
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="u in filteredRecipients" :key="u.id">
                            <label class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40 text-sm">
                                <input type="checkbox" :value="u.id" :checked="recipients.includes(u.id)" @change="toggleRecipient(u.id)"
                                       class="rounded text-[color:var(--color-primary)]">
                                <span class="flex-1">
                                    <span x-text="u.name"></span>
                                    <span class="text-xs text-gray-400" x-text="' — ' + u.office + ' · ' + u.division"></span>
                                </span>
                            </label>
                        </template>
                        <p class="px-3 py-3 text-xs text-gray-400 text-center" x-show="!filteredRecipients.length">No people match your search.</p>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><span x-text="recipients.length"></span> recipient(s) selected.</p>

                    {{-- hidden inputs for submission --}}
                    <template x-for="id in recipients" :key="'h'+id">
                        <input type="hidden" name="recipient_ids[]" :value="id">
                    </template>
                </div>

                {{-- Shared remarks for assign / transfer / multi --}}
                <div x-show="scope === 'none' || scope === 'transfer' || scope === 'multi'" x-cloak class="mt-4">
                    <label class="label" x-text="scope === 'transfer' ? 'Note to the receiving office' : (scope === 'multi' ? 'Note to recipients' : 'Assignment remarks')"></label>
                    <input type="text" name="assign_remarks" value="{{ old('assign_remarks') }}" class="input" placeholder="Optional instructions / note…">
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-2">
                <x-btn :href="route('documents.index')" variant="secondary">Cancel</x-btn>
                <x-btn type="submit">Encode Document</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
