<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Possession ledger — one row per period a document physically sat with a holder
 * (or in an office's claim pool). This is the source of truth for "how long did
 * each staff member hold this document" and "how long has the current holder had it".
 *
 * Time is attributed to whoever PHYSICALLY possesses the document:
 *   - encode/assign/release → counts to the encoder (they still hold the paper)
 *   - once the recipient RECEIVES it → counts to the recipient, and so on
 *   - office claim pool → counts to the office (holder_id null) until claimed
 *   - pending (paused) → no open segment, so that gap is not attributed to anyone
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_possessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'ended_at']);
            $table->index(['holder_id', 'ended_at']);
        });

        // Backfill an open segment for every currently-open document so existing
        // records have a sensible "time with current holder" immediately.
        foreach (DB::table('documents')->whereNotIn('status', ['archived', 'completed'])->get() as $doc) {
            $holderId = null;
            $startedAt = $doc->created_at;

            if ($doc->status === 'received' && $doc->current_holder_id) {
                $holderId = $doc->current_holder_id;
                $startedAt = $doc->received_at ?? $doc->updated_at ?? $doc->created_at;
            } elseif (in_array($doc->status, ['draft', 'released', 'forwarded']) && $doc->current_holder_id) {
                // Assigned/released/forwarded but not yet received → encoder still holds it.
                $holderId = $doc->created_by;
                $startedAt = $doc->created_at;
            } elseif ($doc->status === 'released' && ! $doc->current_holder_id) {
                // Sitting in an office claim pool.
                $holderId = null;
                $startedAt = $doc->released_at ?? $doc->updated_at ?? $doc->created_at;
            } else {
                $holderId = $doc->created_by;
                $startedAt = $doc->created_at;
            }

            DB::table('document_possessions')->insert([
                'document_id' => $doc->id,
                'holder_id' => $holderId,
                'department_id' => $doc->department_id,
                'started_at' => $startedAt,
                'ended_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('documents')->where('id', $doc->id)->update(['possession_started_at' => $startedAt]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_possessions');
    }
};
