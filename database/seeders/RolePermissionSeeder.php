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
            'documents.create',          // encode a new incoming document (per-user toggle)
            'documents.assign',          // (derived from encode) assign a draft to staff
            'documents.release',         // (derived from encode) release / hand over the QR
            'documents.receive',         // confirm physical receipt
            'documents.claim',           // claim unclaimed cross-office transfer (per-user toggle)
            'documents.transfer_office', // send a document to another office (per-user toggle)
            'documents.forward',         // forward to another staff
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
        $staff      = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);

        // Super Admin implicitly gets everything via Gate::before (see AppServiceProvider),
        // but we also sync all permissions so the UI reflects it.
        $superAdmin->syncPermissions(Permission::all());

        // NOTE: encode (documents.create), transfer (documents.transfer_office) and
        // claim (documents.claim) are PER-USER toggles on the user edit screen.
        // assign/release are DERIVED from encode (the encoder acts on their own draft).
        // Heads/division-heads/chiefs still get transfer + claim by role below.

        // Heads: full visibility + logs + reports + can route documents.
        $headPerms = [
            'dashboard.view',
            'documents.view', 'documents.viewAny',
            'documents.receive', 'documents.claim', 'documents.transfer_office',
            'documents.forward', 'documents.archive',
            'reports.view', 'logs.view', 'documentation.view',
        ];
        $head->syncPermissions($headPerms);
        $asstHead->syncPermissions($headPerms);

        // Regular staff: receive / forward / archive what is assigned to them.
        // (Encode / transfer / claim are granted per-user on top of this.)
        $staff->syncPermissions([
            'dashboard.view',
            'documents.view', 'documents.receive', 'documents.forward',
            'documents.archive', 'documentation.view',
        ]);

        // Division Head: route within their division + claim/transfer.
        $divisionHead = Role::firstOrCreate(['name' => 'Division Head', 'guard_name' => 'web']);
        $divisionHead->syncPermissions([
            'dashboard.view',
            'documents.view', 'documents.receive', 'documents.claim', 'documents.transfer_office',
            'documents.forward', 'documents.archive',
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
        $chiefPerms = array_merge($execPerms, ['documents.claim', 'documents.transfer_office']);
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
