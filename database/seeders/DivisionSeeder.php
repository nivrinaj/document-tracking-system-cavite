<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['code' => 'ISDA',  'name' => 'Information Systems and Database Administration'],
            ['code' => 'ADMIN', 'name' => 'Administrative Division'],
            ['code' => 'ICT',   'name' => 'ICT - Technical'],
            ['code' => 'ETD',   'name' => 'Education & Training Division'],
        ];

        $pao = \App\Models\Department::where('code', 'PAO')->first();

        foreach ($divisions as $d) {
            Division::firstOrCreate(['code' => $d['code']], [
                'name' => $d['name'],
                'department_id' => $pao?->id,
                'is_active' => true,
            ]);
        }
    }
}
