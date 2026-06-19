<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="label">Type Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $type?->name) }}" class="input" placeholder="e.g. Voucher" required>
    </div>
    <div>
        <label class="label">Available to department</label>
        <select name="department_id" class="input">
            <option value="">All departments (global)</option>
            @foreach($departments as $dept)<option value="{{ $dept->id }}" @selected(old('department_id', $type?->department_id)==$dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>@endforeach
        </select>
    </div>
</div>
<label class="flex items-center gap-2 text-sm mt-3">
    <input type="hidden" name="requires_voucher" value="0">
    <input type="checkbox" name="requires_voucher" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('requires_voucher', $type?->requires_voucher))>
    Show a <strong>voucher number</strong> field for this type (becomes the QR tail)
</label>
<label class="flex items-center gap-2 text-sm mt-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $type?->is_active ?? true))>
    Active
</label>
