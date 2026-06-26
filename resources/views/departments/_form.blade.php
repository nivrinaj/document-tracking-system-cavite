<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="label">Abbreviation / Code <span class="text-red-500">*</span></label>
        <input type="text" name="code" value="{{ old('code', $department?->code) }}" class="input" placeholder="e.g. PICTO" required>
    </div>
    <div>
        <label class="label">Full Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $department?->name) }}" class="input" placeholder="e.g. Provincial Information and Communications Technology Office" required>
    </div>
</div>
<div>
    <label class="label">Description</label>
    <textarea name="description" rows="2" class="input">{{ old('description', $department?->description) }}</textarea>
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $department?->is_active ?? true))>
    Active
</label>

@php $restrictSelected = old('restricted_doc_types', $department?->restricted_doc_types ?? []); @endphp
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{ acct: {{ old('is_accounting', $department?->is_accounting) ? 'true' : 'false' }}, limit: {{ !empty($restrictSelected) ? 'true' : 'false' }}, types: @js(array_values((array) $restrictSelected)) }">
    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="is_accounting" value="0">
        <input type="checkbox" name="is_accounting" value="1" x-model="acct" class="rounded text-[color:var(--color-primary)]">
        This is the Accounting office
    </label>
    <p class="text-xs text-gray-400 ml-6">When on, this office is limited to <strong>Voucher</strong> &amp; <strong>Payroll</strong> only. (Voucher/Payroll trigger the Amount / Fund / OBR / RC / Nature fields for any office that encodes them.)</p>

    {{-- Limit to document types (for non-accounting offices) --}}
    <div x-show="!acct" x-cloak class="mt-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" x-model="limit" class="rounded text-[color:var(--color-primary)]">
            Limit this office to specific document types
        </label>
        <p class="text-xs text-gray-400 ml-6 mb-2">Leave off to allow all document types. Turn on to pick the only types this office may encode.</p>
        <div x-show="limit" x-cloak class="ml-6 flex flex-wrap gap-2">
            @forelse($documentTypes as $t)
                <button type="button"
                    @click="types.includes(@js($t)) ? types = types.filter(x => x !== @js($t)) : types.push(@js($t))"
                    class="px-3 py-1.5 rounded-lg text-sm border transition"
                    :class="types.includes(@js($t)) ? 'text-white border-transparent' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300'"
                    :style="types.includes(@js($t)) ? 'background: var(--color-primary)' : ''">
                    <span x-show="types.includes(@js($t))">✓ </span>{{ $t }}
                </button>
            @empty
                <span class="text-xs text-gray-400">No document types yet.</span>
            @endforelse
        </div>
        <template x-if="limit"><div><template x-for="t in types" :key="t"><input type="hidden" name="restricted_doc_types[]" :value="t"></template></div></template>
    </div>
</div>

{{-- Completion deadline (turnaround tracking) --}}
@php $slaSelected = old('sla_document_type', $department?->sla_document_type ?? []); @endphp
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{ sla: {{ old('sla_enabled', $department?->sla_enabled) ? 'true' : 'false' }}, types: @js(array_values((array) $slaSelected)) }">
    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="sla_enabled" value="0">
        <input type="checkbox" name="sla_enabled" value="1" x-model="sla" class="rounded text-[color:var(--color-primary)]">
        Set a completion deadline for this department's documents
    </label>
    <p class="text-xs text-gray-400 ml-6">When on, documents are flagged <strong>on-time</strong> or <strong>overdue</strong> in the Processing Time &amp; Overdue report.</p>

    <div x-show="sla" x-cloak class="mt-3 space-y-4">
        <div class="max-w-xs">
            <label class="label">Must be completed within</label>
            <div class="flex items-center gap-2">
                <input type="number" name="sla_days" value="{{ old('sla_days', $department?->sla_days) }}" class="input" min="1" max="365" placeholder="7">
                <span class="text-sm text-gray-500 dark:text-gray-400">days</span>
            </div>
        </div>
        <div>
            <label class="label">Applies to these document types
                <span class="text-gray-400 text-xs font-normal">— tap to select; none = all types</span>
            </label>
            <div class="flex flex-wrap gap-2">
                @forelse($documentTypes as $t)
                    <button type="button"
                        @click="types.includes(@js($t)) ? types = types.filter(x => x !== @js($t)) : types.push(@js($t))"
                        class="px-3 py-1.5 rounded-full text-sm border transition"
                        :class="types.includes(@js($t)) ? 'text-white border-transparent shadow-sm' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-gray-300'"
                        :style="types.includes(@js($t)) ? 'background: var(--color-primary)' : ''">
                        <span x-show="types.includes(@js($t))">✓ </span>{{ $t }}
                    </button>
                @empty
                    <span class="text-xs text-gray-400">No document types yet — add some in the Document Types module.</span>
                @endforelse
            </div>
            <template x-for="t in types" :key="t"><input type="hidden" name="sla_document_type[]" :value="t"></template>
        </div>
    </div>
</div>
