<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            // 'supporting' (default) or 'digital_copy' (the encoder's digitized original).
            $table->string('kind')->default('supporting')->after('document_id');
            // Supporting documents can be a title only (a physical doc with no scan yet).
            $table->string('file_path')->nullable()->change();
            $table->unsignedInteger('file_size')->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
