<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Group chats can be scoped to a division or a department. These columns mark a
 * group conversation as the canonical chat for that unit (so we find-or-create
 * one per division / department rather than spawning duplicates).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('title')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('division_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
