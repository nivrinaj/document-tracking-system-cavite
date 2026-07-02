<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['type', 'recipient', 'subject', 'status', 'error'];

    /** Record a send attempt — 'sent' or 'failed' (with the exception message, if any). */
    public static function record(?string $type, string $recipient, string $subject, string $status, ?string $error = null): void
    {
        static::create([
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
