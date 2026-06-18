<div>
    <label class="label">Role Name <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="input max-w-sm" {{ $role->name === 'Super Admin' ? 'readonly' : '' }} required>
</div>

<div class="mt-5">
    <div class="flex items-center justify-between mb-2">
        <label class="label mb-0">Permissions</label>
        <label class="text-xs text-gray-500 flex items-center gap-1"><input type="checkbox" onclick="document.querySelectorAll('.perm-cb').forEach(c=>c.checked=this.checked)" class="rounded"> Select all</label>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($permissions as $module => $perms)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <div class="font-medium text-sm capitalize mb-2">{{ $module }}</div>
                <div class="space-y-1.5">
                    @foreach($perms as $perm)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="perm-cb rounded text-[color:var(--color-primary)]" name="permissions[]" value="{{ $perm->name }}"
                                @checked(in_array($perm->name, old('permissions', $assigned)))>
                            <span class="text-gray-600 dark:text-gray-300">{{ $perm->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
