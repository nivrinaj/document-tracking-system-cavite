<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Global types available to every office. (Voucher & Payroll are NOT here —
        // they're restricted to Accounting offices via syncAccountingTypes().)
        $types = ['Memorandum', 'Letter', 'Report', 'Invoice', 'Purchase Request', 'Endorsement', 'Attendance', 'Other'];

        foreach ($types as $name) {
            DocumentType::firstOrCreate(
                ['name' => $name],
                ['availability' => 'all', 'requires_voucher' => false, 'is_active' => true],
            );
        }

        // Accounting offices (is_accounting) get their own set: Voucher + Payroll only.
        // These drive the Fund + amount/OBR/RC/nature fields and the fund-based code.
        // Driven by the DB flag, never a hardcoded department code.
        \App\Models\Department::where('is_accounting', true)->get()
            ->each(fn ($dept) => $dept->syncAccountingTypes());
    }
}
