<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $isda  = Division::where('code', 'ISDA')->first();
        $admin = Division::where('code', 'ADMIN')->first();
        $ict   = Division::where('code', 'ICT')->first();
        $etd   = Division::where('code', 'ETD')->first();

        // [name, email, role, division, position]
        $users = [
            ['Super Administrator', 'superadmin@pgc.test', 'Super Admin', $isda, 'System Administrator'],
            ['Department Head', 'head@pgc.test', 'Department Head', $admin, 'Department Head'],
            ['Assistant Department Head', 'asst.head@pgc.test', 'Assistant Department Head', $admin, 'Assistant Department Head'],
            ['Receiving Staff', 'receiving@pgc.test', 'Receiving Staff', $admin, 'Records / Receiving Officer'],
            ['ISDA Staff', 'isda.staff@pgc.test', 'Staff', $isda, 'Database Administrator'],
            ['ICT Staff', 'ict.staff@pgc.test', 'Staff', $ict, 'Technical Support'],
            ['ETD Staff', 'etd.staff@pgc.test', 'Staff', $etd, 'Training Officer'],
            ['Admin Staff', 'admin.staff@pgc.test', 'Staff', $admin, 'Administrative Assistant'],
        ];

        foreach ($users as [$name, $email, $role, $division, $position]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'division_id' => $division?->id,
                    'position' => $position,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
