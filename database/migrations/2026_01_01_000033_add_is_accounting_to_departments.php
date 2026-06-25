<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Marks the Accounting office. When on, this department uses the
            // Voucher/Payroll document types, the Fund picker, the fund-based
            // tracking code, and the amount/OBR/RC/nature fields. DB-driven so
            // we never match on a hardcoded department code.
            $table->boolean('is_accounting')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('is_accounting');
        });
    }
};
