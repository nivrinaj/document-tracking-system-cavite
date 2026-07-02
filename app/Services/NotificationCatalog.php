<?php

namespace App\Services;

/**
 * Single source of truth for every email notification type the system knows
 * how to send. The Notification Settings GUI iterates this list to render a
 * toggle + frequency picker per type — it never hardcodes a type's name/label.
 *
 * To add a new notification type later:
 *   1. Add an entry below with a stable string key (never renamed/reused).
 *   2. Write the Mailable (app/Mail/) and the code that actually triggers it
 *      (a scheduled command for digests, or fired inline for immediate events).
 *   3. Check config($key) before sending, exactly like SendDeadlineReminders does.
 * The GUI, storage, and toggle wiring need no further changes — only the new
 * type entry and its own sending logic.
 */
class NotificationCatalog
{
    /**
     * @return array<string, array{label: string, description: string, frequency_options: array<string,string>, default_frequency: string}>
     */
    public static function types(): array
    {
        return [
            'deadline_reminder' => [
                'label' => 'Deadline reminders',
                'description' => 'Emails a staff member a digest of documents they currently hold that are approaching their deadline or already overdue (uses the same highlight rules as the Deadline column).',
                'frequency_options' => ['daily' => 'Once daily'],
                'default_frequency' => 'daily',
            ],
        ];
    }

    /** The effective per-type config: {enabled, frequency}, merged with catalog defaults. */
    public static function config(): array
    {
        $stored = json_decode((string) \App\Models\Setting::get('notification_config', '{}'), true) ?: [];
        $result = [];
        foreach (self::types() as $key => $meta) {
            $result[$key] = [
                'enabled' => (bool) ($stored[$key]['enabled'] ?? false),
                'frequency' => $stored[$key]['frequency'] ?? $meta['default_frequency'],
            ];
        }

        return $result;
    }

    /** Whether a specific notification type is currently switched on. */
    public static function enabled(string $key): bool
    {
        return (bool) (self::config()[$key]['enabled'] ?? false);
    }
}
