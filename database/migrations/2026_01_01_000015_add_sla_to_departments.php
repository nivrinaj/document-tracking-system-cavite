<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('sla_enabled')->default(false)->after('description');
            $table->unsignedSmallInteger('sla_days')->nullable()->after('sla_enabled');
            // null = all document types; otherwise the type the SLA applies to (e.g. "Voucher")
            $table->string('sla_document_type')->nullable()->after('sla_days');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['sla_enabled', 'sla_days', 'sla_document_type']);
        });
    }
};
