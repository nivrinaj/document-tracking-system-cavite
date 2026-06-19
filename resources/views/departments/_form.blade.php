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
