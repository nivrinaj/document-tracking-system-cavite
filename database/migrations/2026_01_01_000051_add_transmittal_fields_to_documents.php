<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('is_transmittal')->default(false)->after('deadline');
            $table->unsignedInteger('transmittal_quantity')->nullable()->after('is_transmittal');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['is_transmittal', 'transmittal_quantity']);
        });
    }
};
