<?php

namespace App\Console\Commands;

use App\Mail\DeadlineReminderMail;
use App\Models\Document;
use App\Services\NotificationCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders extends Command
{
    protected $signature = 'documents:notify-deadlines';

    protected $description = 'Email each staff member holding a document whose deadline is approaching or overdue (per the same rules as the Deadline column highlight).';

    public function handle(): int
    {
        if (! NotificationCatalog::enabled('deadline_reminder')) {
            $this->info('Deadline reminders are switched off in Notification Settings — nothing sent.');

            return self::SUCCESS;
        }
        if (\App\Models\Setting::get('mail_enabled', '0') !== '1') {
            $this->warn('Deadline reminders are on, but email notifications are not enabled in Notification Settings — nothing sent.');

            return self::SUCCESS;
        }

        $documents = Document::whereNotNull('deadline')
            ->whereNotNull('current_holder_id')
            ->with('currentHolder')
            ->get()
            ->filter(fn ($d) => ! $d->isClosed() && $d->deadlineHighlight() !== null);

        $sent = 0;
        $skippedNoEmail = 0;

        foreach ($documents->groupBy('current_holder_id') as $docs) {
            $user = $docs->first()->currentHolder;
            if (! $user || ! $user->email) {
                $skippedNoEmail++;

                continue;
            }
            Mail::to($user->email)->send(new DeadlineReminderMail($user, $docs));
            $sent++;
        }

        $this->info("Sent {$sent} reminder email(s)."
            .($skippedNoEmail ? " Skipped {$skippedNoEmail} holder(s) with no email address on file." : ''));

        return self::SUCCESS;
    }
}
