<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('transmittal_scope')->default('all')->after('allows_transmittal');
            $table->text('transmittal_departments')->nullable()->after('transmittal_scope');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['transmittal_scope', 'transmittal_departments']);
        });
    }
};
