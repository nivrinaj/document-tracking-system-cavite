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
                  divisions: @js($divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->code.' — '.$d->name, 'department_id' => $d->department_id])),
                  users: @js($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'department_id' => $u->department_id, 'division_id' => $u->division_id, 'division' => $u->division?->code ?? 'Head'])),
                  get srcDivs() { return this.divisions.filter(d => this.srcOffice && String(d.department_id) === String(this.srcOffice)); },
                  get effOffice() { return this.cross ? this.office : this.ownDept; },
                  get distDivs() { return this.divisions.filter(d => this.effOffice && String(d.department_id) === String(this.effOffice)); },
                  get distStaff() { return this.users.filter(u => (!this.effOffice || String(u.department_id) === String(this.effOffice)) && (!this.div || String(u.division_id) === String(this.div))); },
              }">
            @csrf

            {{-- ───── Document details ───── --}}
            <x-card title="Document details">
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

                    <div>
                        <label class="label">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" class="input" required>
                            @foreach(['normal','low','high','urgent'] as $p)
                                <option value="{{ $p }}" @selected(old('priority','normal')===$p)>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Description</label>
                        <textarea name="description" rows="3" class="input" placeholder="Short description of the document…">{{ old('description') }}</textarea>
                    </div>
                </div>
            </x-card>

            {{-- ───── Source / Origin ───── --}}
            <x-card title="Source / Origin">
                <p class="text-xs text-gray-400 mb-3">Where did this document come from?</p>
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

            {{-- ───── Distribution ───── --}}
            <x-card title="Distribution">
                <div class="mb-4">
                    <label class="label">Send as</label>
                    <select name="broadcast_scope" x-model="scope" class="input">
                        <option value="none">Assign to one staff (normal routing)</option>
                        <option value="division">📣 Division memo — broadcast to everyone in my division</option>
                        <option value="department">📣 Department memo — broadcast to everyone in my department</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="scope !== 'none'" x-cloak>Every recipient is notified and acknowledges receipt individually.</p>
                </div>

                {{-- Assign-to-one cascade --}}
                <div x-show="scope === 'none'" x-cloak class="space-y-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <p class="text-xs text-gray-400">Leave staff blank to assign later. @if($crossDept)You can route to <strong>any office</strong>.@endif</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @if($crossDept)
                            <div>
                                <label class="label">Office</label>
                                <select x-model="office" @change="div=''" class="input">
                                    <option value="">— Select office —</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->code }}@if($dept->id == $ownDeptId) (mine)@endif</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="label">Division</label>
                            <select x-model="div" class="input">
                                <option value="">All divisions</option>
                                <template x-for="d in distDivs" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                        </div>
                        <div class="@if(!$crossDept) sm:col-span-2 @endif">
                            <label class="label">Assignee</label>
                            <select name="assignee_id" class="input">
                                <option value="">— Assign later —</option>
                                <template x-for="u in distStaff" :key="u.id">
                                    <option :value="u.id" x-text="u.name + ' — ' + u.division"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Assignment remarks</label>
                        <input type="text" name="assign_remarks" value="{{ old('assign_remarks') }}" class="input" placeholder="Instructions for the assignee…">
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-2">
                <x-btn :href="route('documents.index')" variant="secondary">Cancel</x-btn>
                <x-btn type="submit">Encode Document</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
