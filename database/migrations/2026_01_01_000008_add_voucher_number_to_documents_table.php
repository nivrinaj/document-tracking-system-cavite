<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // For "Voucher" documents, this becomes the tail of the tracking code:
            //   PGC-2026-{voucher_number}
            $table->string('voucher_number')->nullable()->after('document_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('voucher_number');
        });
    }
};
