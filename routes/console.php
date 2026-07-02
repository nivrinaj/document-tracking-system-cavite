<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires a Windows Task Scheduler entry running `php artisan schedule:run`
// every minute on the server — see CLAUDE.md's Notification Settings section.
Schedule::command('documents:notify-deadlines')->dailyAt('07:00');
