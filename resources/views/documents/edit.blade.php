<x-app-layout>
    <x-slot name="header">Edit Document</x-slot>

    <div class="max-w-3xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('documents.update', $document) }}" class="space-y-5"
                  x-data="{
                      docType: '{{ old('document_type', $document->document_type) }}',
                      deadlineTypes: @js($deadlineTypeNames),
                      officeDeadline: {{ ($officeDeadline ?? false) ? 'true' : 'false' }},
                      todayStr: '{{ now()->toDateString() }}',
                      get showDeadline() { return this.officeDeadline && this.deadlineTypes.includes(this.docType); },
                      transmittalTypes: @js($transmittalTypeNames),
                      isTransmittal: {{ old('is_transmittal', $document->is_transmittal) ? 'true' : 'false' }},
                      get showTransmittal() { return this.transmittalTypes.includes(this.docType); },
                  }">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Document Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $document->title) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" x-model="docType" class="input" required>
                            @foreach(['Memorandum','Letter','Report','Voucher','Invoice','Purchase Request','Endorsement','Attendance','Other'] as $t)
                                <option value="{{ $t }}" @selected(old('document_type',$document->document_type)===$t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="docType === 'Voucher'" x-cloak>
                        <label class="label">Voucher Number</label>
                        <input type="text" name="voucher_number" value="{{ old('voucher_number', $document->voucher_number) }}" class="input">
                        <p class="text-xs text-gray-400 mt-1">Note: editing this does not change the existing tracking code <span class="font-mono">{{ $document->tracking_code }}</span>.</p>
                    </div>
                    @if(\App\Models\Document::priorityEnabled())
                    <div>
                        <label class="label">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" class="input" required>
                            @foreach(['normal','low','high','urgent'] as $p)
                                <option value="{{ $p }}" @selected(old('priority',$document->priority)===$p)>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div x-show="showDeadline" x-cloak>
                        <label class="label">Deadline <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                        <input type="date" name="deadline" value="{{ old('deadline', optional($document->deadline)->toDateString()) }}" class="input" :min="todayStr" x-bind:disabled="!showDeadline">
                    </div>
                    <div class="sm:col-span-2" x-show="showTransmittal" x-cloak>
                        <x-toggle name="is_transmittal" x-model="isTransmittal">
                            <span class="block text-sm font-medium">This is a transmittal of multiple <span x-text="docType"></span></span>
                            <span class="block text-xs text-gray-400 mt-0.5">One tracking code covers several physical documents of this type.</span>
                        </x-toggle>
                        <div x-show="isTransmittal" x-cloak class="mt-3 max-w-xs">
                            <label class="label">Quantity <span class="text-red-500">*</span></label>
                            <input type="number" name="transmittal_quantity" value="{{ old('transmittal_quantity', $document->transmittal_quantity) }}" class="input" min="1" max="9999" x-bind:required="isTransmittal">
                        </div>
                    </div>
                    <div>
                        <label class="label">Source / Origin</label>
                        <input type="text" name="source" value="{{ old('source', $document->source) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Division</label>
                        <select name="division_id" class="input">
                            <option value="">— Select division —</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" @selected(old('division_id', $document->division_id)==$div->id)>{{ $div->code }} — {{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Description</label>
                        <textarea name="description" rows="3" class="input">{{ old('description', $document->description) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <x-btn :href="route('documents.show', $document)" variant="secondary">Cancel</x-btn>
                    <x-btn type="submit">Save Changes</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
