<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code')->unique();        // encoded in the QR, used in scan URL
            $table->string('title');
            $table->string('reference_no')->nullable();        // the document's own reference number
            $table->string('document_type')->default('Other'); // Memo, Letter, Invoice, etc.
            $table->text('description')->nullable();
            $table->string('source')->nullable();              // who/where it came from
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            // draft -> released -> received -> (forwarded -> received ...) -> archived/completed
            $table->enum('status', ['draft', 'released', 'received', 'forwarded', 'archived', 'completed'])->default('draft');

            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();      // receiving staff who encoded it
            $table->foreignId('current_holder_id')->nullable()->constrained('users')->nullOnDelete(); // current assignee/holder

            $table->timestamp('received_at')->nullable();   // physically received by the department
            $table->timestamp('released_at')->nullable();   // released by receiving staff
            $table->timestamp('completed_at')->nullable();  // archived / completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
