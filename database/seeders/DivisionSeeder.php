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

        foreach ($divisions as $d) {
            Division::firstOrCreate(['code' => $d['code']], [
                'name' => $d['name'],
                'is_active' => true,
            ]);
        }
    }
}
