<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Move "transfer to another office" and "claim from another office" to per-user
 * capabilities, retire the "Receiving Staff" role (its users become Staff with the
 * matching per-user toggles), and give heads/chiefs the transfer capability by role.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $transfer = Permission::firstOrCreate(['name' => 'documents.transfer_office', 'guard_name' => 'web']);
        $claim = Permission::firstOrCreate(['name' => 'documents.claim', 'guard_name' => 'web']);
        $create = Permission::firstOrCreate(['name' => 'documents.create', 'guard_name' => 'web']);

        // Heads / division heads / chiefs keep transfer + claim by role.
        foreach (['Department Head', 'Assistant Department Head', 'Division Head', 'Chief of Staff (OPG)', 'Chief of Staff (OPVG)'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($transfer);
                if (! $role->hasPermissionTo('documents.claim')) {
                    $role->givePermissionTo($claim);
                }
            }
        }

        // Migrate every Receiving Staff user to Staff + grant the matching per-user toggles.
        if ($receiving = Role::where('name', 'Receiving Staff')->first()) {
            foreach (User::role('Receiving Staff')->get() as $u) {
                $u->givePermissionTo($create);
                $u->givePermissionTo($transfer);
                $u->givePermissionTo($claim);
                $u->syncRoles(['Staff']);
            }
            // Detach from any roles then delete the role.
            $receiving->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversible automatically — re-run seeders to restore roles.
    }
};
