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
            'documents.view',        // view documents that concern me / my department
            'documents.viewAny',     // (legacy) view all in department
            'documents.viewAll',     // view documents across ALL departments (executives)
            'documents.create',      // encode a new incoming document
            'documents.assign',      // assign to a staff
            'documents.release',     // release (hand over the QR)
            'documents.receive',     // confirm physical receipt
            'documents.forward',     // forward to another staff
            'documents.archive',     // archive / complete
            'documents.delete',
            // Admin modules
            'users.manage',
            'departments.manage',
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

        // Division Head: like receiving staff but within their own division.
        $divisionHead = Role::firstOrCreate(['name' => 'Division Head', 'guard_name' => 'web']);
        $divisionHead->syncPermissions([
            'dashboard.view',
            'documents.view', 'documents.create', 'documents.assign', 'documents.release',
            'documents.receive', 'documents.forward', 'documents.archive',
            'reports.view', 'documentation.view',
        ]);

        // ---- Executive / political roles ----
        // Org-wide visibility (documents.viewAll) + can act on documents routed to them.
        $execPerms = [
            'dashboard.view',
            'documents.view', 'documents.viewAll',
            'documents.receive', 'documents.forward', 'documents.archive',
            'reports.view', 'logs.view',
        ];
        foreach ([
            'Provincial Governor',
            'Provincial Vice Governor',
            'Provincial Administrator for Internal Affairs',
        ] as $execName) {
            Role::firstOrCreate(['name' => $execName, 'guard_name' => 'web'])->syncPermissions($execPerms);
        }

        // Chiefs of Staff: run their office workflow + org-wide visibility.
        $chiefPerms = array_merge($execPerms, ['documents.create', 'documents.assign', 'documents.release']);
        foreach (['Chief of Staff (OPG)', 'Chief of Staff (OPVG)'] as $chiefName) {
            Role::firstOrCreate(['name' => $chiefName, 'guard_name' => 'web'])->syncPermissions($chiefPerms);
        }

        // Sangguniang Panlalawigan Member: department-scoped (their office), can act on routed docs.
        Role::firstOrCreate(['name' => 'Sangguniang Panlalawigan Member', 'guard_name' => 'web'])->syncPermissions([
            'dashboard.view', 'documents.view',
            'documents.receive', 'documents.forward', 'documents.archive',
            'reports.view', 'documentation.view',
        ]);

        // Re-sync Super Admin so it picks up any newly created permissions.
        $superAdmin->syncPermissions(Permission::all());
    }
}
