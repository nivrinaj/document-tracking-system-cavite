<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the departments and each department's own divisions (idempotent).
 * Also backfills any existing division/user/document that has no department
 * yet to the default office — safe to run on a live server.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // [dept code => [name, [division code => division name, ...]]]
        $structure = [
            'PICTO' => ['Provincial Information and Communications Technology Office', [
                'SOFTDEV' => 'Software Development',
                'NETINFRA' => 'Network & Infrastructure',
                'TECHSUP' => 'Technical Support',
                'DBA' => 'Database Administration',
            ]],
            'OPG' => ['Office of the Provincial Governor', [
                'OPG-ADM' => 'Administrative Division',
                'OPG-REC' => 'Records Division',
            ]],
            'OPVG' => ['Office of the Provincial Vice Governor', [
                'OPVG-ADM' => 'Administrative Division',
                'OPVG-LEG' => 'Legislative Affairs Division',
            ]],
            'OPAcc' => ['Provincial Accounting Office', [
                'DISB' => 'Disbursement / Vouchers',
                'BOOK' => 'Bookkeeping',
                'FHTD' => 'For Hospital Transaction Division',
            ]],
            'PHRMO' => ['Provincial Human Resource Management Office', [
                'RECRUIT' => 'Recruitment & Selection',
                'HR-REC' => 'HR Records',
            ]],
            'SP' => ['Sangguniang Panlalawigan', [
                'SP-SEC' => 'Secretariat',
            ]],
        ];

        $default = null;
        foreach ($structure as $code => [$name, $divisions]) {
            $dept = Department::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
            $default ??= $dept; // first dept (PICTO) is the backfill default

            // The Accounting office: flag it (drives Voucher/Payroll + fund codes)
            // and track a 7-day SLA on vouchers.
            if ($code === 'OPAcc') {
                $dept->update(['is_accounting' => true, 'sla_enabled' => true, 'sla_days' => 7, 'sla_document_type' => ['Voucher']]);
                $dept->syncAccountingTypes();
            }

            foreach ($divisions as $divCode => $divName) {
                Division::firstOrCreate(['code' => $divCode], [
                    'department_id' => $dept->id,
                    'name' => $divName,
                    'is_active' => true,
                    'is_hospital' => $divCode === 'FHTD',
                ]);
            }
        }

        // Backfill existing records with no department (existing production data).
        if ($default) {
            Division::whereNull('department_id')->update(['department_id' => $default->id]);
            User::whereNull('department_id')->update(['department_id' => $default->id]);
            Document::whereNull('department_id')->update(['department_id' => $default->id]);
        }
    }
}
