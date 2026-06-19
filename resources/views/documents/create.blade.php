<x-app-layout>
    <x-slot name="header">Encode New Document</x-slot>

    <div class="max-w-3xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('documents.store') }}" class="space-y-5"
                  x-data="{ docType: '{{ old('document_type', $documentTypes->first()->name ?? 'Memorandum') }}', voucherNo: '{{ old('voucher_number') }}', voucherTypes: @js($voucherTypeNames) }">
                @csrf

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

                    {{-- Voucher number: only for voucher-type docs; becomes the tail of the tracking code --}}
                    <div x-show="voucherTypes.includes(docType)" x-cloak>
                        <label class="label">Voucher Number <span class="text-red-500">*</span></label>
                        <input type="text" name="voucher_number" x-model="voucherNo" class="input" placeholder="e.g. DV-00123"
                               x-bind:required="docType === 'Voucher'">
                        <p class="text-xs text-gray-400 mt-1">Tracking code will be
                            <span class="font-mono">PGC-{{ date('Y') }}-<span x-text="(voucherNo || 'XXXX').toUpperCase().replace(/[^A-Z0-9\-]/g,'')"></span></span>
                        </p>
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

                    <div>
                        <label class="label">Source / Origin</label>
                        <input type="text" name="source" value="{{ old('source') }}" class="input" placeholder="Where did it come from?">
                    </div>

                    <div>
                        <label class="label">Division</label>
                        <select name="division_id" class="input">
                            <option value="">— Select division —</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" @selected(old('division_id', auth()->user()->division_id)==$div->id)>{{ $div->code }} — {{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label">Description</label>
                        <textarea name="description" rows="3" class="input" placeholder="Short description of the document…">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- Distribution: single assignee OR a memo broadcast --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-4" x-data="{ scope: '{{ old('broadcast_scope', 'none') }}' }">
                    <h3 class="font-medium text-sm mb-3">Distribution</h3>

                    <div class="mb-4">
                        <label class="label">Send as</label>
                        <select name="broadcast_scope" x-model="scope" class="input">
                            <option value="none">Assign to one staff (normal routing)</option>
                            <option value="division">📣 Division memo — broadcast to everyone in my division</option>
                            <option value="department">📣 Department memo — broadcast to everyone in my department</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" x-show="scope !== 'none'" x-cloak>Every recipient is notified and acknowledges receipt individually. No single holder.</p>
                    </div>

                    <div x-show="scope === 'none'" x-cloak>
                    <p class="text-xs text-gray-400 mb-3">You can assign now, or do it later from the document page. You can assign to anyone, including yourself.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Assignee</label>
                            <select name="assignee_id" class="input">
                                <option value="">— Assign later —</option>
                                @foreach($users->groupBy(fn($u) => $u->division?->code ?? 'No division') as $group => $groupUsers)
                                    <optgroup label="{{ $group }}">
                                        @foreach($groupUsers as $u)
                                            <option value="{{ $u->id }}" @selected(old('assignee_id')==$u->id)>{{ $u->name }} ({{ $u->position ?? 'Staff' }})</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Assignment remarks</label>
                            <input type="text" name="assign_remarks" value="{{ old('assign_remarks') }}" class="input" placeholder="Instructions for the assignee…">
                        </div>
                    </div>
                    </div>{{-- /x-show none --}}
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <x-btn :href="route('documents.index')" variant="secondary">Cancel</x-btn>
                    <x-btn type="submit">Encode Document</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
