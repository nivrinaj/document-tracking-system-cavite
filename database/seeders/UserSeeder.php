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
            ['Super Administrator', 'superadmin', 'superadmin@pgc.test', 'Super Admin', 'PICTO', 'DBA', 'System Administrator'],
            ['PICTO Department Head', 'head', 'head@pgc.test', 'Department Head', 'PICTO', null, 'Department Head'],
            ['PICTO Assistant Head', 'asst.head', 'asst.head@pgc.test', 'Assistant Department Head', 'PICTO', null, 'Assistant Department Head'],
            ['PICTO Receiving Staff', 'receiving', 'receiving@pgc.test', 'Staff', 'PICTO', 'TECHSUP', 'Records / Receiving Officer'],
            ['SoftDev Division Head', 'softdev.head', 'softdev.head@pgc.test', 'Division Head', 'PICTO', 'SOFTDEV', 'Division Head'],
            ['Database Staff', 'dba.staff', 'dba.staff@pgc.test', 'Staff', 'PICTO', 'DBA', 'Database Administrator'],
            ['Network Staff', 'net.staff', 'net.staff@pgc.test', 'Staff', 'PICTO', 'NETINFRA', 'Network Engineer'],
            ['Tech Support Staff', 'tech.staff', 'tech.staff@pgc.test', 'Staff', 'PICTO', 'TECHSUP', 'Technical Support'],

            // Accounting office (for SLA / voucher testing)
            ['Accounting Head', 'acctg.head', 'acctg.head@pgc.test', 'Department Head', 'PACCO', null, 'Provincial Accountant'],
            ['Disbursement Receiving', 'acctg.receiving', 'acctg.receiving@pgc.test', 'Staff', 'PACCO', 'DISB', 'Disbursement Officer'],
            ['Disbursement Staff', 'disb.staff', 'disb.staff@pgc.test', 'Staff', 'PACCO', 'DISB', 'Voucher Processor'],

            // HR office
            ['HR Head', 'hr.head', 'hr.head@pgc.test', 'Department Head', 'PHRMO', null, 'HR Officer'],
            ['HR Staff', 'hr.staff', 'hr.staff@pgc.test', 'Staff', 'PHRMO', 'RECRUIT', 'HR Assistant'],

            // Executive / political offices
            ['Provincial Governor', 'governor', 'governor@pgc.test', 'Provincial Governor', 'OPG', null, 'Provincial Governor'],
            ['Provincial Vice Governor', 'vicegovernor', 'vicegovernor@pgc.test', 'Provincial Vice Governor', 'OPVG', null, 'Provincial Vice Governor'],
            ['Chief of Staff - OPG', 'cos.opg', 'cos.opg@pgc.test', 'Chief of Staff (OPG)', 'OPG', 'OPG-ADM', 'Chief of Staff'],
            ['Chief of Staff - OPVG', 'cos.opvg', 'cos.opvg@pgc.test', 'Chief of Staff (OPVG)', 'OPVG', 'OPVG-ADM', 'Chief of Staff'],
            ['Provincial Administrator', 'administrator', 'administrator@pgc.test', 'Provincial Administrator for Internal Affairs', 'OPG', null, 'Provincial Administrator'],
            ['SP Member', 'sp.member', 'sp.member@pgc.test', 'Sangguniang Panlalawigan Member', 'SP', 'SP-SEC', 'Board Member'],
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

        // Per-user capabilities (encode / transfer / claim) — these replace the old
        // "Receiving Staff" role. Granted directly on top of the Staff role.
        $caps = [
            'receiving' => ['documents.create', 'documents.transfer_office', 'documents.claim'],
            'acctg.receiving' => ['documents.create', 'documents.transfer_office', 'documents.claim'],
        ];
        foreach ($caps as $username => $perms) {
            if ($u = User::where('username', $username)->first()) {
                foreach ($perms as $perm) {
                    $u->givePermissionTo($perm);
                }
            }
        }
    }
}
