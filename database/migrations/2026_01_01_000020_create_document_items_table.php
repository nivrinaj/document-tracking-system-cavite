<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items on a "route slip" — one QR/document can carry several individual
 * sub-documents. Each can be cleared (good to go) or rejected (returned to origin)
 * independently, so partial outcomes are tracked. Gated by the
 * `enable_route_items` setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('pending'); // pending | cleared | rejected
            $table->text('remarks')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
