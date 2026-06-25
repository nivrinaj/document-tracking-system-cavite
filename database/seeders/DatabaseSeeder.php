<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            DocumentTypeSeeder::class,
            AccountingSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            DocumentationSeeder::class,
            DemoDocumentSeeder::class,
        ]);
    }
}
