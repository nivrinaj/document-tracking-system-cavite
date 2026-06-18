<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="label">Code <span class="text-red-500">*</span></label>
        <input type="text" name="code" value="{{ old('code', $division?->code) }}" class="input" placeholder="e.g. ISDA" required>
    </div>
    <div>
        <label class="label">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $division?->name) }}" class="input" required>
    </div>
</div>
<div>
    <label class="label">Description</label>
    <textarea name="description" rows="2" class="input">{{ old('description', $division?->description) }}</textarea>
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $division?->is_active ?? true))>
    Active
</label>
