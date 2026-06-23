<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trim leading/trailing whitespace and stray newlines from existing chat
 * messages so old bubbles size correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('messages')->get(['id', 'body']) as $m) {
            $trimmed = trim((string) $m->body);
            if ($trimmed !== $m->body) {
                DB::table('messages')->where('id', $m->id)->update(['body' => $trimmed]);
            }
        }
    }

    public function down(): void
    {
        // no-op
    }
};
