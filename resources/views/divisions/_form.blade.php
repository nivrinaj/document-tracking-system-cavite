@if($division?->department)
    {{-- Department is fixed by the parent department page --}}
    <div>
        <label class="label">Department</label>
        <input type="text" class="input bg-gray-100 dark:bg-gray-700 cursor-not-allowed" value="{{ $division->department->code }} — {{ $division->department->name }}" disabled>
        <input type="hidden" name="department_id" value="{{ $division->department_id }}">
    </div>
@else
    <div>
        <label class="label">Department</label>
        <select name="department_id" class="input">
            <option value="">— Select department —</option>
            @foreach(($departments ?? collect()) as $dept)
                <option value="{{ $dept->id }}" @selected(old('department_id', $division?->department_id)==$dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
@endif
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

<div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-2">
    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="is_hospital" value="0">
        <input type="checkbox" name="is_hospital" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_hospital', $division?->is_hospital ?? false))>
        Hospital transactions division
    </label>
    <p class="text-xs text-gray-400 ml-6">For an Accounting office only. When on, encoders here see only <strong>General Fund</strong> &amp; <strong>Trust Fund</strong>, run on their own sequence, and every tracking code gets an <strong>“-H”</strong> suffix.</p>
</div>
