<x-app-layout>
    <x-slot name="header">Encode New Document</x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('documents.store') }}" class="space-y-6"
              x-data="{
                  docType: '{{ old('document_type', $documentTypes->first()->name ?? 'Memorandum') }}',
                  voucherNo: '{{ old('voucher_number') }}',
                  voucherTypes: @js($voucherTypeNames),
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
                  divisions: @js($divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->code.' — '.$d->name, 'department_id' => $d->department_id])),
                  users: @js($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'department_id' => $u->department_id, 'division_id' => $u->division_id, 'division' => $u->division?->code ?? 'Head'])),
                  allUsers: @js($allUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'office' => $u->department?->code ?? '—', 'division' => $u->division?->code ?? 'Head'])),
                  get srcDivs() { return this.divisions.filter(d => this.srcOffice && String(d.department_id) === String(this.srcOffice)); },
                  get ownDivs() { return this.divisions.filter(d => String(d.department_id) === String(this.ownDept)); },
                  get ownStaff() { return this.users.filter(u => !this.div || String(u.division_id) === String(this.div)); },
                  get filteredRecipients() { const q = this.recipientSearch.toLowerCase().trim(); return this.allUsers.filter(u => !q || u.name.toLowerCase().includes(q) || u.office.toLowerCase().includes(q)); },
                  toggleRecipient(id) { const i = this.recipients.indexOf(id); if (i === -1) this.recipients.push(id); else this.recipients.splice(i, 1); },
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

                    <div>
                        <label class="label">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" x-model="docType" class="input" required>
                            @forelse($documentTypes as $t)
                                <option value="{{ $t->name }}" @selected(old('document_type')===$t->name)>{{ $t->name }}</option>
                            @empty
                                <option value="Other">Other</option>
                            @endforelse
                        </select>
                    </div>

                    <div x-show="voucherTypes.includes(docType)" x-cloak>
                        <label class="label">Voucher Number <span class="text-red-500">*</span></label>
                        <input type="text" name="voucher_number" x-model="voucherNo" class="input" placeholder="e.g. DV-00123" x-bind:required="voucherTypes.includes(docType)">
                        <p class="text-xs text-gray-400 mt-1">Code: <span class="font-mono">{{ \App\Models\Document::trackingPrefix() }}-{{ date('Y') }}-<span x-text="(voucherNo || 'XXXX').toUpperCase().replace(/[^A-Z0-9\-]/g,'')"></span></span></p>
                    </div>

                    <div>
                        <label class="label">Reference No.</label>
                        <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="input" placeholder="e.g. MEMO-2026-001">
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
                        <select name="source_department_id" x-model="srcOffice" @change="srcDiv=''" class="input">
                            <option value="">— Select office —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}@if($dept->id == $ownDeptId) (mine)@endif</option>
                            @endforeach
                            <option value="external">Other / External client</option>
                        </select>
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
                        <option value="none">Assign to a staff in my office</option>
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
                            <select x-model="div" class="input">
                                <option value="">All divisions</option>
                                <template x-for="d in ownDivs" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="label">Assignee</label>
                            <select name="assignee_id" class="input">
                                <option value="">— Assign later —</option>
                                <template x-for="u in ownStaff" :key="u.id">
                                    <option :value="u.id" x-text="u.name + ' — ' + u.division"></option>
                                </template>
                            </select>
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
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] text-xs">
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
