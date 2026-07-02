<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Null = inherit the global default (Document::defaultDeadlineIncludedStatuses()).
            $table->json('deadline_included_statuses')->nullable()->after('deadline_overdue_color');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('deadline_included_statuses');
        });
    }
};
