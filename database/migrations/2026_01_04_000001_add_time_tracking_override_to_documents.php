<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-document override of the department's calendar-days tracking setting.
     * Null on both columns = inherit the department's default entirely. A
     * department turning the feature ON only makes the choice AVAILABLE — it no
     * longer forces every one of its documents into calendar-days mode.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('time_tracking_mode')->nullable()->after('forwarded_to_head_at');
            $table->boolean('calendar_days_include_weekends')->nullable()->after('time_tracking_mode');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['time_tracking_mode', 'calendar_days_include_weekends']);
        });
    }
};
