<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Make "can encode documents" a PER-USER setting instead of role-based.
 * Anyone who can currently encode (via their role) keeps it as a direct
 * permission, then the permission is removed from every role (except Super
 * Admin, which has everything anyway). From then on, encode access is toggled
 * per account on the user edit screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'documents.create', 'guard_name' => 'web']);

        // Preserve current access: grant the direct permission to everyone who has it now.
        foreach (User::all() as $user) {
            if ($user->hasPermissionTo('documents.create') && ! $user->hasRole('Super Admin')) {
                $user->givePermissionTo($perm);
            }
        }

        // Remove the role-based grant from every role except Super Admin.
        foreach (Role::where('name', '!=', 'Super Admin')->get() as $role) {
            if ($role->hasPermissionTo('documents.create')) {
                $role->revokePermissionTo($perm);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversed automatically — re-seed roles to restore role-based encode.
    }
};
