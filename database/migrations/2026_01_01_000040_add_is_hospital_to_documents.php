<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            // Set at encode time from the encoder's division (immutable record that
            // this was a hospital-division transaction). Queried directly — never
            // inferred from the tracking-code text or the mutable current division.
            $t->boolean('is_hospital')->default(false)->after('nature_of_transaction');
        });

        // One-time historical backfill for documents encoded before this column existed:
        // the legacy "-H" suffix is the only record of their hospital origin.
        DB::table('documents')->where('tracking_code', 'like', '%-H')->update(['is_hospital' => true]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $t) {
            $t->dropColumn('is_hospital');
        });
    }
};
