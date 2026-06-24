<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->unsignedInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('document_id');
        });

        // A short label of where a distributed document went (for the list view).
        Schema::table('documents', function (Blueprint $table) {
            $table->string('distribution_summary')->nullable()->after('is_broadcast');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('distribution_summary');
        });
    }
};
