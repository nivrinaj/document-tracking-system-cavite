<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsibility_centers', function (Blueprint $table) {
            $table->boolean('is_hospital')->default(false)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('responsibility_centers', function (Blueprint $table) {
            $table->dropColumn('is_hospital');
        });
    }
};
