<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $perm = Permission::firstOrCreate(['name' => 'accounting.manage', 'guard_name' => 'web']);

        // Department Heads manage their office's accounting reference data.
        foreach (['Department Head', 'Assistant Department Head'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($perm);
            }
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'accounting.manage')->delete();
    }
};
