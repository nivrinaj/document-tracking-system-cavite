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
<x-toggle name="is_active" label="Active" :checked="old('is_active', $department?->is_active ?? true)" />

@php $restrictSelected = old('restricted_doc_types', $department?->restricted_doc_types ?? []); @endphp
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{ acct: {{ old('is_accounting', $department?->is_accounting) ? 'true' : 'false' }}, limit: {{ !empty($restrictSelected) ? 'true' : 'false' }}, types: @js(array_values((array) $restrictSelected)) }">
    <x-toggle name="is_accounting" x-model="acct" label="Voucher & Payroll office">
        <span class="block text-xs text-gray-400 mt-0.5">When on, this office is limited to <strong>Voucher</strong> &amp; <strong>Payroll</strong>, and encoding either shows the extra <strong>Amount / Fund / OBR / RC / Nature</strong> fields. When off, an office encoding a Voucher/Payroll sees only the regular fields.</span>
    </x-toggle>

    {{-- Limit to document types (for non-accounting offices) --}}
    <div x-show="!acct" x-cloak class="mt-4">
        <x-toggle x-model="limit" label="Limit this office to specific document types">
            <span class="block text-xs text-gray-400 mt-0.5 mb-2">Leave off to allow all document types. Turn on to pick the only types this office may encode.</span>
        </x-toggle>
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

{{-- Deadline tracking (opt-in per office) --}}
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{
        deadlineOn: {{ old('deadline_enabled', $department?->deadline_enabled) ? 'true' : 'false' }},
        customize: {{ !empty($department?->deadline_highlight_rules) || $department?->deadline_overdue_color ? 'true' : 'false' }},
     }">
    <x-toggle x-model="deadlineOn" name="deadline_enabled" label="Enable deadlines for this office">
        <span class="block text-xs text-gray-400 mt-0.5">When on, encoders in this office can set a <strong>Deadline</strong> on document types marked “requires a deadline”, and this office’s tracking list shows a Deadline column with colour highlighting as it nears.</span>
    </x-toggle>

    <div x-show="deadlineOn" x-cloak class="ml-[3.25rem] mt-3 space-y-3">
        <x-toggle x-model="customize" label="Customize highlight colors for this office">
            <span class="block text-xs text-gray-400 mt-0.5">Otherwise this office uses the Super Admin's default colors (System Settings → Deadline Highlighting).</span>
        </x-toggle>
        <div x-show="customize" x-cloak>
            <x-deadline-rules-editor prefix="dept" :rules="$department?->deadline_highlight_rules ?? []" :overdue-color="$department?->deadline_overdue_color ?: \App\Models\Document::defaultDeadlineOverdueColor()" />
        </div>
        <input type="hidden" name="customize_deadline_colors" :value="customize ? '1' : '0'">
    </div>
</div>

{{-- Broadcast acknowledgment layout (opt-in per office) --}}
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2">
    <x-toggle name="broadcast_ack_layout" label="Use the tabbed acknowledgment layout for this office’s broadcasts" :checked="old('broadcast_ack_layout', $department?->broadcast_ack_layout)">
        <span class="block text-xs text-gray-400 mt-0.5">When on, memos broadcast by this office show recipients in tabs by employment status, grouped by division, in a table of name / position / date received — instead of the default chip list.</span>
    </x-toggle>
</div>

{{-- Internal time-tracking display (calendar days vs. working hours) --}}
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{ calDays: {{ old('time_tracking_mode', $department?->time_tracking_mode) === 'calendar_days' ? 'true' : 'false' }} }">
    <x-toggle x-model="calDays" label="Show calendar days instead of working hours for this office's documents">
        <span class="block text-xs text-gray-400 mt-0.5">
            For this office's own view only — age/idle/turnaround on documents currently with them count plain calendar days rather than official working hours.
            The underlying working-hours engine keeps running unchanged for everyone else, so this stays safe to turn on/off per office as more offices get interconnected later.
        </span>
    </x-toggle>
    <input type="hidden" name="time_tracking_mode" :value="calDays ? 'calendar_days' : 'working_hours'">
</div>

{{-- Completion deadline (turnaround tracking) --}}
@php $slaSelected = old('sla_document_type', $department?->sla_document_type ?? []); @endphp
<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2"
     x-data="{ sla: {{ old('sla_enabled', $department?->sla_enabled) ? 'true' : 'false' }}, types: @js(array_values((array) $slaSelected)) }">
    <x-toggle name="sla_enabled" x-model="sla" label="Set a completion deadline for this department's documents">
        <span class="block text-xs text-gray-400 mt-0.5">When on, documents are flagged <strong>on-time</strong> or <strong>overdue</strong> in the Processing Time &amp; Overdue report.</span>
    </x-toggle>

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
