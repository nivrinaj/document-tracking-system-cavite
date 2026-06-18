<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- Permissions, grouped by module ----
        $permissions = [
            // Dashboard
            'dashboard.view',
            // Document tracking
            'documents.view',        // view documents that concern me
            'documents.viewAny',     // view ALL documents in the department (heads)
            'documents.create',      // encode a new incoming document
            'documents.assign',      // assign to a staff
            'documents.release',     // release (hand over the QR)
            'documents.receive',     // confirm physical receipt
            'documents.forward',     // forward to another staff
            'documents.archive',     // archive / complete
            'documents.delete',
            // Admin modules
            'users.manage',
            'divisions.manage',
            'roles.manage',
            'reports.view',
            'logs.view',
            'settings.manage',
            'documentation.view',
            'documentation.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ---- Roles ----
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $head       = Role::firstOrCreate(['name' => 'Department Head', 'guard_name' => 'web']);
        $asstHead   = Role::firstOrCreate(['name' => 'Assistant Department Head', 'guard_name' => 'web']);
        $receiving  = Role::firstOrCreate(['name' => 'Receiving Staff', 'guard_name' => 'web']);
        $staff      = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);

        // Super Admin implicitly gets everything via Gate::before (see AppServiceProvider),
        // but we also sync all permissions so the UI reflects it.
        $superAdmin->syncPermissions(Permission::all());

        // Heads: full visibility + logs + reports + can act on documents.
        $headPerms = [
            'dashboard.view',
            'documents.view', 'documents.viewAny',
            'documents.create', 'documents.assign', 'documents.release',
            'documents.receive', 'documents.forward', 'documents.archive',
            'reports.view', 'logs.view', 'documentation.view',
        ];
        $head->syncPermissions($headPerms);
        $asstHead->syncPermissions($headPerms);

        // Receiving staff: the encoding/QR/release workflow.
        $receiving->syncPermissions([
            'dashboard.view',
            'documents.view', 'documents.create', 'documents.assign',
            'documents.release', 'documents.receive', 'documents.forward',
            'documents.archive', 'reports.view', 'documentation.view',
        ]);

        // Regular staff: receive / forward / archive what is assigned to them.
        $staff->syncPermissions([
            'dashboard.view',
            'documents.view', 'documents.receive', 'documents.forward',
            'documents.archive', 'documentation.view',
        ]);
    }
}
