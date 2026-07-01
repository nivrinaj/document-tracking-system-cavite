<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // 'working_hours' (default, shared engine) or 'calendar_days' (this
            // office's own internal view only — see Document::elapsedSeconds()).
            $table->string('time_tracking_mode')->default('working_hours')->after('deadline_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('time_tracking_mode');
        });
    }
};
