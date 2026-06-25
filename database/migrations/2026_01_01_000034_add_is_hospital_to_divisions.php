<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            // Marks a Hospital-transactions division inside the Accounting office.
            // When on, only General Fund + Trust Fund are offered, the codes run on
            // their own sequence, and an "-H" suffix is appended. DB-driven so we
            // never match on a hardcoded division code (was "FHTD").
            $table->boolean('is_hospital')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('is_hospital');
        });
    }
};
