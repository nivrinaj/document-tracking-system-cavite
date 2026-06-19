<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent. Safe to run on a live server after the multi-department migration:
 * it creates the departments and backfills any existing division/user/document
 * that has no department yet to the default office (so nothing breaks).
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $pao = Department::firstOrCreate(['code' => 'PAO'], ['name' => "Provincial Administrator's Office", 'is_active' => true]);
        Department::firstOrCreate(['code' => 'OPG'], ['name' => 'Office of the Provincial Governor', 'is_active' => true]);
        Department::firstOrCreate(['code' => 'OPVG'], ['name' => 'Office of the Provincial Vice Governor', 'is_active' => true]);
        Department::firstOrCreate(['code' => 'SP'], ['name' => 'Sangguniang Panlalawigan', 'is_active' => true]);

        // Backfill anything not yet assigned to a department (existing production data).
        Division::whereNull('department_id')->update(['department_id' => $pao->id]);
        User::whereNull('department_id')->update(['department_id' => $pao->id]);
        Document::whereNull('department_id')->update(['department_id' => $pao->id]);
    }
}
