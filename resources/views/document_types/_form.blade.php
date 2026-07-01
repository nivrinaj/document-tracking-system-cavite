<div class="max-w-md">
    <label class="label">Type Name <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $type?->name) }}" class="input" placeholder="e.g. Voucher" required>
    <p class="text-[11px] text-gray-400 mt-1">All document types are available to every office. To limit a specific office to only certain types, use <strong>Departments → edit → “Limit to document types”</strong>.</p>
</div>

<label class="flex items-center gap-2 text-sm mt-4">
    <input type="hidden" name="requires_voucher" value="0">
    <input type="checkbox" name="requires_voucher" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('requires_voucher', $type?->requires_voucher))>
    Show a legacy <strong>voucher number</strong> field for this type
</label>
<label class="flex items-center gap-2 text-sm mt-2">
    <input type="hidden" name="requires_deadline" value="0">
    <input type="checkbox" name="requires_deadline" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('requires_deadline', $type?->requires_deadline))>
    Show a <strong>Deadline</strong> field for this type <span class="text-gray-400">(e.g. Letter) — only in offices with deadlines enabled</span>
</label>
<label class="flex items-center gap-2 text-sm mt-2">
    <input type="hidden" name="allows_transmittal" value="0">
    <input type="checkbox" name="allows_transmittal" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('allows_transmittal', $type?->allows_transmittal))>
    Allow encoding this as a <strong>transmittal</strong> (multiple documents under one tracking code) <span class="text-gray-400">— adds a quantity field at encode time</span>
</label>
<label class="flex items-center gap-2 text-sm mt-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $type?->is_active ?? true))>
    Active
</label>
