<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            // Stable notification-type key (e.g. 'deadline_reminder'), or null for
            // one-off sends like the settings-page test email — never matched by
            // a display label, same convention as everything else in this app.
            $table->string('type')->nullable();
            $table->string('recipient');
            $table->string('subject');
            $table->string('status'); // 'sent' or 'failed'
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
