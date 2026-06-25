<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            // holiday | suspension (global) ; dept_dayoff ; user_leave ; user_undertime
            $table->string('type', 20);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('worked_hours', 5, 2)->nullable(); // for undertime: hours actually worked that day
            $table->string('label')->nullable();   // e.g. holiday name
            $table->string('reason')->nullable();   // mandatory for leave/undertime/day-off
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'type']);
            $table->index(['user_id', 'date']);
            $table->index(['department_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
