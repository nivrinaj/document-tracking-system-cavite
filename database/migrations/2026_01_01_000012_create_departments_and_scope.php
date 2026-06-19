<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-department support. Purely ADDITIVE so it is safe to run on a server
 * that already has live data (no rows are deleted). Existing records get a
 * NULL department_id; the DepartmentSeeder backfills them to a default
 * department (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('division_id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('division_id')->constrained('departments')->nullOnDelete();
            $table->boolean('is_broadcast')->default(false)->after('status');
        });

        Schema::table('document_assignees', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_assignees', fn (Blueprint $t) => $t->dropColumn('acknowledged_at'));
        Schema::table('documents', function (Blueprint $t) {
            $t->dropConstrainedForeignId('department_id');
            $t->dropColumn('is_broadcast');
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('department_id'));
        Schema::table('divisions', fn (Blueprint $t) => $t->dropConstrainedForeignId('department_id'));
        Schema::dropIfExists('departments');
    }
};
