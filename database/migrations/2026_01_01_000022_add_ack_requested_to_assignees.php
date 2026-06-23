<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track WHO was actually asked to acknowledge a document, separate from the
 * "concerned staff" list. Previously distributing a document flipped the whole
 * thing to a broadcast, which made every prior holder look like an acknowledger.
 * Now only people with ack_requested_at set are asked to acknowledge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_assignees', function (Blueprint $table) {
            $table->timestamp('ack_requested_at')->nullable()->after('acknowledged_at');
        });

        // Backfill: existing broadcast/memo documents requested an ack from each
        // of their assignees — set it from when they were attached.
        $broadcastIds = DB::table('documents')->where('is_broadcast', true)->pluck('id');
        if ($broadcastIds->isNotEmpty()) {
            DB::table('document_assignees')
                ->whereIn('document_id', $broadcastIds)
                ->update(['ack_requested_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        Schema::table('document_assignees', function (Blueprint $table) {
            $table->dropColumn('ack_requested_at');
        });
    }
};
