<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Global types available to every department. (department_id = null)
        $types = [
            ['name' => 'Memorandum', 'requires_voucher' => false],
            ['name' => 'Letter', 'requires_voucher' => false],
            ['name' => 'Report', 'requires_voucher' => false],
            ['name' => 'Voucher', 'requires_voucher' => true],
            ['name' => 'Invoice', 'requires_voucher' => false],
            ['name' => 'Purchase Request', 'requires_voucher' => false],
            ['name' => 'Endorsement', 'requires_voucher' => false],
            ['name' => 'Attendance', 'requires_voucher' => false],
            ['name' => 'Other', 'requires_voucher' => false],
        ];

        foreach ($types as $t) {
            DocumentType::firstOrCreate(
                ['name' => $t['name'], 'department_id' => null],
                ['requires_voucher' => $t['requires_voucher'], 'is_active' => true],
            );
        }

        // Accounting offices (is_accounting) get their own set: Voucher + Payroll only.
        // These drive the Fund + amount/OBR/RC/nature fields and the fund-based code.
        // Driven by the DB flag, never a hardcoded department code.
        \App\Models\Department::where('is_accounting', true)->get()
            ->each(fn ($dept) => $dept->syncAccountingTypes());
    }
}
