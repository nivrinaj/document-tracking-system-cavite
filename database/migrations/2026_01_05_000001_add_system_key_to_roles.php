<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stable identifier for the handful of "system" roles the application's
     * own logic depends on (Super Admin, Department Head, etc.) — separate from
     * `name`, which stays a free-text label an admin can rename anytime from the
     * Roles & Permissions page without breaking any authorization logic. This is
     * the exact same id-vs-label separation already used for departments/divisions
     * (match by `department_id`, never by `code`/`name`), applied to roles.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('system_key')->nullable()->unique()->after('name');
        });

        // One-time backfill: match TODAY's names to a stable key. After this,
        // the application never matches roles by name again — renaming any of
        // these roles from the Roles & Permissions page is safe.
        $map = [
            'Super Admin' => 'super_admin',
            'Department Head' => 'department_head',
            'Assistant Department Head' => 'assistant_department_head',
            'Division Head' => 'division_head',
            'Staff' => 'staff',
            'Receiving Staff' => 'receiving_staff',
        ];
        foreach ($map as $name => $key) {
            DB::table('roles')->where('name', $name)->update(['system_key' => $key]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('system_key');
        });
    }
};
