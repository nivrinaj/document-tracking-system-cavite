<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('employment_status')->nullable()->after('position');
        });

        // Best-effort backfill: last word -> last name, the rest -> first name.
        // The full `name` is left intact for display until the user is next edited.
        foreach (DB::table('users')->select('id', 'name')->get() as $u) {
            $parts = preg_split('/\s+/', trim((string) $u->name)) ?: [];
            if (count($parts) >= 2) {
                $last = array_pop($parts);
                $first = implode(' ', $parts);
            } else {
                $first = '';
                $last = (string) $u->name;
            }
            DB::table('users')->where('id', $u->id)->update([
                'first_name' => $first,
                'last_name' => $last,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'employment_status']);
        });
    }
};
