<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'documents.claim', 'guard_name' => 'web']);

        $claimRoles = [
            'Super Admin',
            'Department Head',
            'Assistant Department Head',
            'Receiving Staff',
            'Division Head',
            'Chief of Staff (OPG)',
            'Chief of Staff (OPVG)',
        ];

        foreach ($claimRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo('documents.claim')) {
                $role->givePermissionTo($perm);
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'documents.claim')->delete();
    }
};
