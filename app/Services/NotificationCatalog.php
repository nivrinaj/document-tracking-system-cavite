<?php

namespace App\Services;

/**
 * Single source of truth for every email notification type the system knows
 * how to send. The Notification Settings GUI iterates this list to render a
 * toggle + frequency + time picker per type — it never hardcodes a type's
 * name/label.
 *
 * To add a new notification type later:
 *   1. Add an entry below with a stable string key (never renamed/reused).
 *   2. Write the Mailable (app/Mail/) and the code that actually triggers it
 *      (dispatched from routes/console.php via NotificationCatalog::isDue()
 *      for a digest, or fired inline for an immediate event).
 *   3. Check config($key) before sending, exactly like SendDeadlineReminders does.
 * The GUI, storage, and toggle wiring need no further changes — only the new
 * type entry and its own sending logic.
 */
class NotificationCatalog
{
    /**
     * Shared repeat options every scheduled (digest-style) notification type can offer.
     * 'daily'/'weekdays' are anchored to a specific time-of-day; the interval
     * options ignore the configured time and just fire on that cadence.
     */
    public const FREQUENCY_OPTIONS = [
        'daily' => 'Once daily, at a set time',
        'weekdays' => 'Weekdays only (Mon–Fri), at a set time',
        'every_8_hours' => 'Every 8 hours',
        'every_4_hours' => 'Every 4 hours',
        'hourly' => 'Every hour',
    ];

    /** Frequencies where the configured time-of-day is actually used. */
    public const TIME_BASED_FREQUENCIES = ['daily', 'weekdays'];

    /**
     * @return array<string, array{label: string, description: string, frequency_options: array<string,string>, default_frequency: string, default_time: string}>
     */
    public static function types(): array
    {
        return [
            'deadline_reminder' => [
                'label' => 'Deadline reminders',
                'description' => 'Emails a staff member a digest of documents they currently hold that are approaching their deadline or already overdue (uses the same highlight rules as the Deadline column).',
                'frequency_options' => self::FREQUENCY_OPTIONS,
                'default_frequency' => 'daily',
                'default_time' => '07:00',
            ],
        ];
    }

    /** The effective per-type config: {enabled, frequency, time}, merged with catalog defaults. */
    public static function config(): array
    {
        $stored = json_decode((string) \App\Models\Setting::get('notification_config', '{}'), true) ?: [];
        $result = [];
        foreach (self::types() as $key => $meta) {
            $result[$key] = [
                'enabled' => (bool) ($stored[$key]['enabled'] ?? false),
                'frequency' => $stored[$key]['frequency'] ?? $meta['default_frequency'],
                'time' => $stored[$key]['time'] ?? $meta['default_time'],
            ];
        }

        return $result;
    }

    /** Whether a specific notification type is currently switched on. */
    public static function enabled(string $key): bool
    {
        return (bool) (self::config()[$key]['enabled'] ?? false);
    }

    /**
     * Whether right now is the moment a scheduled type should fire, given its
     * configured frequency (+ time-of-day for daily/weekdays). Called once a
     * minute from routes/console.php — cheap, no side effects.
     */
    public static function isDue(array $typeConfig, ?\Illuminate\Support\Carbon $now = null): bool
    {
        $now = $now ?: now();
        $time = $typeConfig['time'] ?? '07:00';

        return match ($typeConfig['frequency'] ?? 'daily') {
            'daily' => $now->format('H:i') === $time,
            'weekdays' => ! $now->isWeekend() && $now->format('H:i') === $time,
            'every_8_hours' => $now->hour % 8 === 0 && $now->minute === 0,
            'every_4_hours' => $now->hour % 4 === 0 && $now->minute === 0,
            'hourly' => $now->minute === 0,
            default => false,
        };
    }
}
