<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\NatureOfTransaction;
use App\Models\ResponsibilityCenter;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        // Funds: [name, code, report_code, is_dev_fund, hospital_available]
        $funds = [
            ['General Funds', '101', 'GF', false, true],
            ['SEF', '221', 'SEF', false, false],
            ['Trust Fund', '401', 'TF', false, true],
            ['Gen. Fund 20% Development Fund', '101', 'GFDF', true, false],
        ];
        foreach ($funds as $i => [$name, $code, $reportCode, $dev, $hosp]) {
            Fund::firstOrCreate(
                ['name' => $name],
                ['code' => $code, 'report_code' => $reportCode, 'is_dev_fund' => $dev, 'hospital_available' => $hosp, 'sort_order' => $i, 'is_active' => true],
            );
        }

        // Nature of transaction options: [name => report code].
        $i = 0;
        foreach (['Payment' => 'Payt.', 'Reimbursement' => 'Reimb.', 'Liquidation' => 'Liq.', 'Cash Advance' => 'CA', 'Refund' => 'Refund'] as $name => $reportCode) {
            NatureOfTransaction::firstOrCreate(['name' => $name], ['report_code' => $reportCode, 'sort_order' => $i++, 'is_active' => true]);
        }

        // A couple of sample responsibility centers (Office/Unit/Project + code).
        foreach ([['OPG', 'OPG'], ['OPVG', 'OPVG'], ['OPAcc', 'OPAcc']] as $i => [$name, $code]) {
            ResponsibilityCenter::firstOrCreate(['name' => $name], ['code' => $code, 'sort_order' => $i, 'is_active' => true]);
        }

        // Ensure each Accounting office has a Hospital-transactions division.
        // Driven by the is_accounting flag, never a hardcoded department code.
        foreach (Department::where('is_accounting', true)->get() as $dept) {
            Division::firstOrCreate(
                ['code' => 'FHTD', 'department_id' => $dept->id],
                ['name' => 'For Hospital Transaction Division', 'is_active' => true, 'is_hospital' => true],
            );
        }
    }
}
