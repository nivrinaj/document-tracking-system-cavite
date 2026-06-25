<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->after('document_type')->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2)->nullable()->after('fund_id');
            $table->string('obr_no')->nullable()->after('amount');
            $table->foreignId('responsibility_center_id')->nullable()->after('obr_no')->constrained()->nullOnDelete();
            $table->string('rc_code')->nullable()->after('responsibility_center_id');     // "SPA - 20% Development Fund"
            $table->string('nature_of_transaction')->nullable()->after('rc_code');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_id');
            $table->dropConstrainedForeignId('responsibility_center_id');
            $table->dropColumn(['amount', 'obr_no', 'rc_code', 'nature_of_transaction']);
        });
    }
};
