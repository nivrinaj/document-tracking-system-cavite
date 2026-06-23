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

        return view('users.index', [
            'users' => $query->paginate((int) \App\Models\Setting::get('records_per_page', 12))->withQueryString(),
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);
        $this->syncEncodePermission($user, $request->boolean('can_encode'));

        return redirect()->route('users.index')->with('success', 'User created.');
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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);
        $this->syncEncodePermission($user, $request->boolean('can_encode'));

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    /** Grant/revoke the per-account encode (documents.create) permission. */
    private function syncEncodePermission(User $user, bool $canEncode): void
    {
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'documents.create', 'guard_name' => 'web']
        );

        if ($canEncode) {
            if (! $user->hasDirectPermission('documents.create')) {
                $user->givePermissionTo($perm);
            }
        } elseif ($user->hasDirectPermission('documents.create')) {
            $user->revokePermissionTo($perm);
        }
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }
}
