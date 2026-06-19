<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $div = fn ($code) => Division::where('code', $code)->first()?->id;
        $dep = fn ($code) => Department::where('code', $code)->first()?->id;

        // [name, username, email, role, department_code, division_code|null, position]
        $users = [
            ['Super Administrator', 'superadmin', 'superadmin@pgc.test', 'Super Admin', 'PAO', 'ISDA', 'System Administrator'],
            ['Department Head', 'head', 'head@pgc.test', 'Department Head', 'PAO', null, 'Department Head'],
            ['Assistant Department Head', 'asst.head', 'asst.head@pgc.test', 'Assistant Department Head', 'PAO', null, 'Assistant Department Head'],
            ['Receiving Staff', 'receiving', 'receiving@pgc.test', 'Receiving Staff', 'PAO', 'ADMIN', 'Records / Receiving Officer'],
            ['ISDA Division Head', 'isda.head', 'isda.head@pgc.test', 'Division Head', 'PAO', 'ISDA', 'Division Head'],
            ['ISDA Staff', 'isda.staff', 'isda.staff@pgc.test', 'Staff', 'PAO', 'ISDA', 'Database Administrator'],
            ['ICT Staff', 'ict.staff', 'ict.staff@pgc.test', 'Staff', 'PAO', 'ICT', 'Technical Support'],
            ['ETD Staff', 'etd.staff', 'etd.staff@pgc.test', 'Staff', 'PAO', 'ETD', 'Training Officer'],
            ['Admin Staff', 'admin.staff', 'admin.staff@pgc.test', 'Staff', 'PAO', 'ADMIN', 'Administrative Assistant'],

            // Executive / political offices
            ['Provincial Governor', 'governor', 'governor@pgc.test', 'Provincial Governor', 'OPG', null, 'Provincial Governor'],
            ['Provincial Vice Governor', 'vicegovernor', 'vicegovernor@pgc.test', 'Provincial Vice Governor', 'OPVG', null, 'Provincial Vice Governor'],
            ['Chief of Staff - OPG', 'cos.opg', 'cos.opg@pgc.test', 'Chief of Staff (OPG)', 'OPG', null, 'Chief of Staff'],
            ['Chief of Staff - OPVG', 'cos.opvg', 'cos.opvg@pgc.test', 'Chief of Staff (OPVG)', 'OPVG', null, 'Chief of Staff'],
            ['Provincial Administrator', 'administrator', 'administrator@pgc.test', 'Provincial Administrator for Internal Affairs', 'PAO', null, 'Provincial Administrator'],
            ['SP Member', 'sp.member', 'sp.member@pgc.test', 'Sangguniang Panlalawigan Member', 'SP', null, 'Board Member'],
        ];

        foreach ($users as [$name, $username, $email, $role, $deptCode, $divCode, $position]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => $username,
                    'password' => Hash::make('password'),
                    'department_id' => $dep($deptCode),
                    'division_id' => $divCode ? $div($divCode) : null,
                    'position' => $position,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->forceFill([
                'username' => $username,
                'department_id' => $dep($deptCode),
                'division_id' => $divCode ? $div($divCode) : null,
            ])->save();
            $user->syncRoles([$role]);
        }
    }
}
