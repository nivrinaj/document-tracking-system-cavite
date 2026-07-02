<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['division', 'department', 'roles'])->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($department = $request->input('department_id')) {
            $query->where('department_id', $department);
        }
        if ($division = $request->input('division_id')) {
            $query->where('division_id', $division);
        }
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }
        if (($status = $request->input('status')) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }

        $perPage = (int) $request->input('per_page', \App\Models\Setting::get('records_per_page', 12));
        if (! in_array($perPage, [12, 25, 50, 100], true)) {
            $perPage = (int) \App\Models\Setting::get('records_per_page', 12);
        }

        return view('users.index', [
            'users' => $query->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(),
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'divisions' => Division::orderBy('name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $useDefaultPassword = $request->boolean('use_default_password');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => $useDefaultPassword ? ['nullable'] : ['required', 'confirmed', Password::defaults()],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', Rule::in(User::EMPLOYMENT_STATUSES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = User::composeName($data['first_name'], $data['middle_name'] ?? null, $data['last_name']);

        $user = User::create([
            'name' => $data['name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($useDefaultPassword ? User::DEFAULT_PASSWORD : $data['password']),
            'must_change_password' => $useDefaultPassword,
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);
        $this->syncEncodePermission($user, $request->boolean('can_encode'));
        $this->syncDirectPermission($user, 'documents.transfer_office', $request->boolean('can_transfer_office'));
        $this->syncDirectPermission($user, 'documents.claim', $request->boolean('can_claim'));
        $this->syncDirectPermission($user, 'calendar.manage', $request->boolean('can_manage_calendar'));

        $dept = optional(\App\Models\Department::find($data['department_id'] ?? null))->code ?? '(none)';
        $pwNote = $useDefaultPassword ? ', started on the default password (must change on first login)' : '';
        \App\Models\ActivityLog::record('users.store', "Created user: {$data['name']} ({$data['username']}), Role: {$data['role']}, Dept: {$dept}{$pwNote}", $user);

        return redirect()->route('users.index')->with('success', $useDefaultPassword
            ? "User created with the default password (\"".User::DEFAULT_PASSWORD."\"). They'll be asked to change it on first login."
            : 'User created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'divisions' => Division::orderBy('name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', Rule::in(User::EMPLOYMENT_STATUSES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = User::composeName($data['first_name'], $data['middle_name'] ?? null, $data['last_name']);

        $oldRole = $user->roles->first()?->name ?? '';
        $oldDeptCode = optional($user->department)->code ?? '(none)';
        $oldDivCode = optional($user->division)->code ?? '(none)';
        $oldActive = $user->is_active;

        $changes = [];
        if ($user->name !== $data['name']) $changes[] = 'Name "' . $user->name . '" → "' . $data['name'] . '"';
        if ($user->username !== $data['username']) $changes[] = 'Username "' . $user->username . '" → "' . $data['username'] . '"';
        if (($user->email ?? '') !== ($data['email'] ?? '')) $changes[] = 'Email "' . ($user->email ?: '(none)') . '" → "' . ($data['email'] ?: '(none)') . '"';
        if ((string) ($user->department_id ?? '') !== (string) ($data['department_id'] ?? '')) {
            $newDeptCode = optional(\App\Models\Department::find($data['department_id'] ?? null))->code ?? '(none)';
            $changes[] = 'Dept ' . $oldDeptCode . ' → ' . $newDeptCode;
        }
        if ((string) ($user->division_id ?? '') !== (string) ($data['division_id'] ?? '')) {
            $newDivCode = optional(Division::find($data['division_id'] ?? null))->code ?? '(none)';
            $changes[] = 'Division ' . $oldDivCode . ' → ' . $newDivCode;
        }
        if ($oldRole !== $data['role']) $changes[] = 'Role ' . ($oldRole ?: '(none)') . ' → ' . $data['role'];
        if ($oldActive !== $request->boolean('is_active')) $changes[] = 'Active ' . ($oldActive ? 'ON' : 'OFF') . ' → ' . ($request->boolean('is_active') ? 'ON' : 'OFF');
        if (($user->position ?? '') !== ($data['position'] ?? '')) $changes[] = 'Position changed';
        if (! empty($data['password'])) $changes[] = 'Password changed';

        $capLabels = ['can_encode' => 'Can encode', 'can_transfer_office' => 'Can transfer', 'can_claim' => 'Can claim', 'can_manage_calendar' => 'Can manage calendar'];
        $capPerms = ['can_encode' => 'documents.create', 'can_transfer_office' => 'documents.transfer_office', 'can_claim' => 'documents.claim', 'can_manage_calendar' => 'calendar.manage'];
        foreach ($capLabels as $field => $label) {
            $old = $user->hasDirectPermission($capPerms[$field]);
            $new = $request->boolean($field);
            if ($old !== $new) $changes[] = $label . ' ' . ($old ? 'ON' : 'OFF') . ' → ' . ($new ? 'ON' : 'OFF');
        }

        if (($user->employment_status ?? '') !== ($data['employment_status'] ?? '')) $changes[] = 'Employment status "' . ($user->employment_status ?: '(none)') . '" → "' . ($data['employment_status'] ?: '(none)') . '"';

        $user->update([
            'name' => $data['name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);
        $this->syncEncodePermission($user, $request->boolean('can_encode'));
        $this->syncDirectPermission($user, 'documents.transfer_office', $request->boolean('can_transfer_office'));
        $this->syncDirectPermission($user, 'documents.claim', $request->boolean('can_claim'));
        $this->syncDirectPermission($user, 'calendar.manage', $request->boolean('can_manage_calendar'));

        $desc = 'Updated user: ' . $data['name'] . (count($changes) ? ' — ' . implode('; ', $changes) : ' (no changes)');
        \App\Models\ActivityLog::record('users.update', $desc, $user);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    /** Grant/revoke the per-account encode (documents.create) permission. */
    private function syncEncodePermission(User $user, bool $canEncode): void
    {
        $this->syncDirectPermission($user, 'documents.create', $canEncode);
    }

    /** Grant/revoke a per-account direct permission. */
    private function syncDirectPermission(User $user, string $name, bool $grant): void
    {
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        if ($grant && ! $user->hasDirectPermission($name)) {
            $user->givePermissionTo($perm);
        } elseif (! $grant && $user->hasDirectPermission($name)) {
            $user->revokePermissionTo($perm);
        }
    }

    public function destroy(Request $request, User $user)
    {
        if (\App\Models\Setting::get('enable_user_delete', '1') !== '1') {
            return back()->with('error', 'Deleting user accounts is currently disabled in System Settings.');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    /** Reset an existing account to the default password and force a change on next login — for accounts created before that option existed. */
    public function resetPassword(Request $request, User $user)
    {
        $user->update([
            'password' => Hash::make(User::DEFAULT_PASSWORD),
            'must_change_password' => true,
        ]);

        \App\Models\ActivityLog::record(
            'users.resetPassword',
            "Reset password to default for: {$user->name} ({$user->username}) — must change on next login",
            $user
        );

        return back()->with('success', "Password reset to the default (\"".User::DEFAULT_PASSWORD."\"). {$user->name} will be required to change it on their next login.");
    }

    /** Bulk-delete selected users. Always skips your own account and Super Admin accounts (same protection resetData() already applies), so you can't accidentally lock yourself out. */
    public function bulkDestroy(Request $request)
    {
        if (\App\Models\Setting::get('enable_user_delete', '1') !== '1') {
            return back()->with('error', 'Deleting user accounts is currently disabled in System Settings.');
        }

        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return back()->with('error', 'No users selected.');
        }

        $users = User::whereIn('id', $ids)->get();
        $deletedNames = [];
        $skippedSelf = false;
        $skippedSuperAdmin = 0;

        foreach ($users as $user) {
            if ($user->id === $request->user()->id) {
                $skippedSelf = true;

                continue;
            }
            if ($user->hasSystemRole(User::SYS_SUPER_ADMIN)) {
                $skippedSuperAdmin++;

                continue;
            }
            $deletedNames[] = $user->name;
            $user->delete();
        }

        if (empty($deletedNames)) {
            return back()->with('error', 'No users were deleted'.($skippedSuperAdmin ? ' — Super Admin accounts are protected from bulk delete' : '').'.');
        }

        \App\Models\ActivityLog::record('users.bulkDestroy', 'Bulk-deleted '.count($deletedNames).' user(s): '.implode(', ', $deletedNames));

        $msg = count($deletedNames).' user(s) deleted.';
        if ($skippedSelf) $msg .= ' Your own account was skipped.';
        if ($skippedSuperAdmin) $msg .= " {$skippedSuperAdmin} Super Admin account(s) were skipped.";

        return back()->with('success', $msg);
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }
}
