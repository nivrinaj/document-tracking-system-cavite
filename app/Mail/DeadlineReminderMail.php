<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Not queued (ShouldQueue) — sent synchronously by SendDeadlineReminders,
 * which itself only ever runs from the scheduler or the "Send now" test
 * button, never from a user-facing request. No confirmed persistent queue
 * worker on this IIS deployment, so a queued mailable would otherwise sit
 * unsent indefinitely (same reasoning as BackupController running synchronously).
 */
class DeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Collection $documents)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Deadline reminder — '.$this->documents->count().' document(s) need attention'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.deadline-reminder');
    }
}
