<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links between related documents (e.g. a request and its supporting voucher).
 * Stored symmetrically (a row each way) so either document shows the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_document_id')->constrained('documents')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'related_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
