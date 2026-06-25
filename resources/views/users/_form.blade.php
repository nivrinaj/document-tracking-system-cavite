@php $current = $user?->roles->first()?->name; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
     x-data="{
        dept: '{{ old('department_id', $user?->department_id) }}',
        divId: '',
        divisions: @js($divisions->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name,'department_id'=>$d->department_id])),
        get visibleDivs() { return this.divisions.filter(d => (!this.dept || String(d.department_id) === String(this.dept)) || String(d.id) === String(this.divId)); },
        init() {
            // Set the current division AFTER the options render so the select preselects it
            // reliably (x-model on x-for options doesn't preselect on first paint).
            this.$nextTick(() => { this.divId = '{{ old('division_id', $user?->division_id) }}'; });
        }
     }">
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
            <template x-for="d in visibleDivs" :key="d.id">
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

@php
    $caps = [
        ['is_active', 'Active account', 'The user can log in and use the system.', old('is_active', $user?->is_active ?? true)],
        ['can_encode', 'Can encode documents', 'Add (encode) new documents — and assign &amp; release their own drafts.', old('can_encode', $user?->canEncode() ?? false)],
        ['can_transfer_office', 'Can transfer to another office', 'Send a document they hold to another office\'s claim pool.', old('can_transfer_office', $user ? $user->canTransferOffice() : false)],
        ['can_claim', 'Can claim from another office', 'Claim / receive documents transferred into their office\'s pool.', old('can_claim', $user ? $user->canClaimFromOffice() : false)],
        ['can_manage_calendar', 'Can manage work calendar', 'Set their department\'s day-offs and colleagues\' leave / undertime (each needs a reason and is logged).', old('can_manage_calendar', $user ? $user->hasDirectPermission('calendar.manage') : false)],
    ];
@endphp
<div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Access &amp; capabilities</p>
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($caps as [$name, $title, $desc, $checked])
            <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                <span class="min-w-0">
                    <span class="block text-sm font-medium">{{ $title }}</span>
                    <span class="block text-xs text-gray-400">{!! $desc !!}</span>
                </span>
                <span class="relative inline-flex shrink-0 items-center">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked)>
                    <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                    <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                </span>
            </label>
        @endforeach
    </div>
    <p class="text-[11px] text-gray-400 mt-2">Super Admins always have every capability. These toggles let you grant abilities to any account regardless of role.</p>
</div>
