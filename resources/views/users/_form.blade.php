@php $current = $user?->roles->first()?->name; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
     x-data="{ dept: '{{ old('department_id', $user?->department_id) }}', divId: '{{ old('division_id', $user?->division_id) }}', divisions: @js($divisions->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name,'department_id'=>$d->department_id])) }">
    <div>
        <label class="label">Full Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user?->name) }}" class="input" required>
    </div>
    <div>
        <label class="label">Username <span class="text-red-500">*</span></label>
        <input type="text" name="username" value="{{ old('username', $user?->username) }}" class="input" required autocomplete="off" placeholder="e.g. juan.delacruz">
        <p class="text-xs text-gray-400 mt-1">Used to log in. Letters, numbers, dashes and underscores only.</p>
    </div>
    <div>
        <label class="label">Email <span class="text-gray-400 text-xs">(optional — for password reset)</span></label>
        <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="input" autocomplete="off">
    </div>
    <div>
        <label class="label">Department</label>
        <select name="department_id" x-model="dept" @change="divId=''" class="input">
            <option value="">— None —</option>
            @foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Division <span class="text-gray-400 text-xs">(heads can leave blank)</span></label>
        <select name="division_id" x-model="divId" class="input">
            <option value="">— None —</option>
            <template x-for="d in divisions.filter(x => !dept || String(x.department_id) === String(dept))" :key="d.id">
                <option :value="d.id" x-text="d.name"></option>
            </template>
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
