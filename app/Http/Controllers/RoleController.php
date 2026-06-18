<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')->with('permissions')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('roles.create', [
            'permissions' => $this->groupedPermissions(),
            'role' => new Role(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        return view('roles.edit', [
            'role' => $role,
            'permissions' => $this->groupedPermissions(),
            'assigned' => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Protect the Super Admin role name.
        if ($role->name !== 'Super Admin') {
            $role->update(['name' => $data['name']]);
        }

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['Super Admin', 'Department Head', 'Assistant Department Head', 'Receiving Staff', 'Staff'])) {
            return back()->with('error', 'Core roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is still assigned to users.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function show(Role $role)
    {
        return redirect()->route('roles.edit', $role);
    }

    /** Group permissions by their module prefix (documents.*, users.*, ...). */
    private function groupedPermissions()
    {
        return Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        });
    }
}
