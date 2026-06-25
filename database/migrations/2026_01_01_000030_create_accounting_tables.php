<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Funds (e.g. General Fund 101, SEF 221, Trust 401, 20% Dev Fund 101).
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');                       // numeric prefix used in the tracking code
            $table->boolean('is_dev_fund')->default(false); // own sequence; distinguishes 101 (Dev) from 101 (General)
            $table->boolean('hospital_available')->default(false); // usable by the Hospital (FHTD) division
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Responsibility Centers — Office/Unit/Project with its own code (master list).
        Schema::create('responsibility_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');   // Office / Unit / Project, e.g. "OPG"
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Nature of transaction options (Payment, Reimbursement, …).
        Schema::create('nature_of_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Annual auto-increment counters per sequence group (STD / DEV / HOSP), per year.
        Schema::create('tracking_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('year', 4);
            $table->string('sequence_key', 20);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['year', 'sequence_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_sequences');
        Schema::dropIfExists('nature_of_transactions');
        Schema::dropIfExists('responsibility_centers');
        Schema::dropIfExists('funds');
    }
};
