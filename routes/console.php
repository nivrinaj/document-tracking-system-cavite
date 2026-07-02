<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatches every enabled notification type at its own GUI-configured time/
// frequency (Notification Settings page) — checked every minute so admins can
// change the schedule without a code deploy. Requires a Windows Task Scheduler
// entry running `php artisan schedule:run` every minute on the server.
Schedule::call(function () {
    foreach (\App\Services\NotificationCatalog::config() as $key => $cfg) {
        if (! $cfg['enabled'] || ! \App\Services\NotificationCatalog::isDue($cfg)) {
            continue;
        }
        match ($key) {
            'deadline_reminder' => Artisan::call('documents:notify-deadlines'),
            default => null,
        };
    }
})->everyMinute()->name('dispatch-due-notifications')->withoutOverlapping();
