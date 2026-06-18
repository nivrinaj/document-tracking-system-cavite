@php $current = $user?->roles->first()?->name; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="label">Full Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user?->name) }}" class="input" required>
    </div>
    <div>
        <label class="label">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="input" required>
    </div>
    <div>
        <label class="label">Division</label>
        <select name="division_id" class="input">
            <option value="">— None —</option>
            @foreach($divisions as $d)<option value="{{ $d->id }}" @selected(old('division_id', $user?->division_id)==$d->id)>{{ $d->code }} — {{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Role <span class="text-red-500">*</span></label>
        <select name="role" class="input" required>
            <option value="">— Select role —</option>
            @foreach($roles as $r)<option value="{{ $r->name }}" @selected(old('role', $current)===$r->name)>{{ $r->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Position</label>
        <input type="text" name="position" value="{{ old('position', $user?->position) }}" class="input" placeholder="e.g. Records Officer">
    </div>
    <div>
        <label class="label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user?->phone) }}" class="input">
    </div>
    <div>
        <label class="label">Password {{ $user ? '(leave blank to keep)' : '*' }}</label>
        <input type="password" name="password" class="input" {{ $user ? '' : 'required' }} autocomplete="new-password">
    </div>
    <div>
        <label class="label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
    </div>
</div>

<label class="flex items-center gap-2 text-sm mt-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $user?->is_active ?? true))>
    Account is active (can log in)
</label>
