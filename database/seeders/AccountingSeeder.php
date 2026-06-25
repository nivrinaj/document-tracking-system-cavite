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
        // Funds: [name, code, is_dev_fund, hospital_available]
        $funds = [
            ['General Funds', '101', false, true],
            ['SEF', '221', false, false],
            ['Trust Fund', '401', false, true],
            ['Gen. Fund 20% Development Fund', '101', true, false],
        ];
        foreach ($funds as $i => [$name, $code, $dev, $hosp]) {
            Fund::firstOrCreate(
                ['name' => $name],
                ['code' => $code, 'is_dev_fund' => $dev, 'hospital_available' => $hosp, 'sort_order' => $i, 'is_active' => true],
            );
        }

        // Nature of transaction options.
        foreach (['Payment', 'Reimbursement', 'Liquidation', 'Cash Advance', 'Refund'] as $i => $name) {
            NatureOfTransaction::firstOrCreate(['name' => $name], ['sort_order' => $i, 'is_active' => true]);
        }

        // A couple of sample responsibility centers (Office/Unit/Project + code).
        foreach ([['OPG', 'OPG'], ['OPVG', 'OPVG'], ['PACCO', 'PACCO']] as $i => [$name, $code]) {
            ResponsibilityCenter::firstOrCreate(['name' => $name], ['code' => $code, 'sort_order' => $i, 'is_active' => true]);
        }

        // Ensure the Hospital division exists under Accounting (PACCO).
        if ($pacco = Department::where('code', 'PACCO')->first()) {
            Division::firstOrCreate(
                ['code' => 'FHTD'],
                ['name' => 'For Hospital Transaction Division', 'department_id' => $pacco->id, 'is_active' => true],
            );
        }
    }
}
