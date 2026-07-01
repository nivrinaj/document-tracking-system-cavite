<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time default: set every existing user's employment status to
     * Permanent/Regular where it isn't set yet. New users pick their own
     * (the field is optional) and admins can change these anytime.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('employment_status')->update([
            'employment_status' => 'Permanent/Regular',
        ]);
    }

    public function down(): void
    {
        // No-op: we don't want to wipe employment statuses on rollback.
    }
};
