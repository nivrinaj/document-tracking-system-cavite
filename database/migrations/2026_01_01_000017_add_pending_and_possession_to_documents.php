<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Pending = the holder paused work on it (awaiting action of the origin).
            // While pending, the possession clock is stopped and it's excluded from
            // the "aging / bottlenecks" report.
            $table->boolean('is_pending')->default(false)->after('status');
            $table->timestamp('pending_at')->nullable()->after('is_pending');

            // When the CURRENT possession segment started (denormalised from the
            // possession ledger for fast "time with current holder" display/reports).
            $table->timestamp('possession_started_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['is_pending', 'pending_at', 'possession_started_at']);
        });
    }
};
