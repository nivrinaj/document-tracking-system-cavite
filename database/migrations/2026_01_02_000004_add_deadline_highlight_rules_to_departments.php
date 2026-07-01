<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Per-office override of the global deadline highlight rules — only
            // used when this office has deadline_enabled and has customized them.
            $table->json('deadline_highlight_rules')->nullable()->after('deadline_enabled');
            $table->string('deadline_overdue_color')->nullable()->after('deadline_highlight_rules');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['deadline_highlight_rules', 'deadline_overdue_color']);
        });
    }
};
