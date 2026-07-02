<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\MailSettings;
use App\Services\NotificationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function edit()
    {
        return view('notification-settings.edit', [
            'mail' => [
                'enabled' => Setting::get('mail_enabled', '0') === '1',
                'host' => Setting::get('mail_host', ''),
                'port' => Setting::get('mail_port', '587'),
                'encryption' => Setting::get('mail_encryption', 'tls'),
                'username' => Setting::get('mail_username', ''),
                'has_password' => MailSettings::password() !== '',
                'from_address' => Setting::get('mail_from_address', ''),
                'from_name' => Setting::get('mail_from_name', ''),
            ],
            'types' => NotificationCatalog::types(),
            'config' => NotificationCatalog::config(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', ''])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'notify' => ['array'],
            'notify.*.enabled' => ['nullable', 'boolean'],
            'notify.*.frequency' => ['nullable', 'string', 'max:50'],
        ]);

        $changes = [];
        $oldEnabled = Setting::get('mail_enabled', '0') === '1';
        $newEnabled = $request->boolean('mail_enabled');
        if ($oldEnabled !== $newEnabled) $changes[] = 'Email notifications '.($oldEnabled ? 'ON' : 'OFF').' → '.($newEnabled ? 'ON' : 'OFF');

        foreach (['mail_host' => 'SMTP host', 'mail_port' => 'SMTP port', 'mail_encryption' => 'Encryption', 'mail_username' => 'SMTP username', 'mail_from_address' => 'From address', 'mail_from_name' => 'From name'] as $key => $label) {
            $old = (string) Setting::get($key, '');
            $new = (string) ($data[$key] ?? '');
            if ($old !== $new) $changes[] = "{$label} \"{$old}\" → \"{$new}\"";
        }

        Setting::put('mail_enabled', $newEnabled ? '1' : '0');
        Setting::put('mail_host', $data['mail_host'] ?? '');
        Setting::put('mail_port', (string) ($data['mail_port'] ?? '587'));
        Setting::put('mail_encryption', $data['mail_encryption'] ?? 'tls');
        Setting::put('mail_username', $data['mail_username'] ?? '');
        Setting::put('mail_from_address', $data['mail_from_address'] ?? '');
        Setting::put('mail_from_name', $data['mail_from_name'] ?? '');

        // Password: leave the stored one alone unless a new value was actually typed.
        if (! empty($data['mail_password'])) {
            MailSettings::setPassword($data['mail_password']);
            $changes[] = 'SMTP password updated';
        }

        $notifyChanges = [];
        $storedConfig = [];
        foreach (NotificationCatalog::types() as $key => $meta) {
            $wasEnabled = NotificationCatalog::enabled($key);
            $isEnabled = (bool) ($data['notify'][$key]['enabled'] ?? false);
            $frequency = $data['notify'][$key]['frequency'] ?? $meta['default_frequency'];
            $storedConfig[$key] = ['enabled' => $isEnabled, 'frequency' => $frequency];
            if ($wasEnabled !== $isEnabled) $notifyChanges[] = $meta['label'].' '.($wasEnabled ? 'ON' : 'OFF').' → '.($isEnabled ? 'ON' : 'OFF');
        }
        Setting::put('notification_config', json_encode($storedConfig));
        $changes = array_merge($changes, $notifyChanges);

        ActivityLog::record('notifications.settings.update', 'Notification settings'.(count($changes) ? ': '.implode('; ', $changes) : ' saved (no changes)'));

        return back()->with('success', 'Notification settings saved.');
    }

    /** Send a one-off test email to confirm the SMTP settings actually work. */
    public function sendTest(Request $request)
    {
        $data = $request->validate(['test_email' => ['required', 'email']]);

        if (Setting::get('mail_enabled', '0') !== '1') {
            return back()->with('error', 'Turn on and save email notifications first, then send a test.');
        }

        try {
            Mail::raw('This is a test email from '.config('app.name').' — your SMTP settings are working correctly.', function ($message) use ($data) {
                $message->to($data['test_email'])->subject('Test email — '.config('app.name'));
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: '.$e->getMessage());
        }

        ActivityLog::record('notifications.settings.test', 'Sent a test email to '.$data['test_email']);

        return back()->with('success', 'Test email sent to '.$data['test_email'].' — check the inbox (and spam folder).');
    }

    /** Manually trigger a notification type right now, instead of waiting for its schedule — for testing. */
    public function runNow(string $type)
    {
        if (! array_key_exists($type, NotificationCatalog::types())) {
            abort(404);
        }
        if (! NotificationCatalog::enabled($type)) {
            return back()->with('error', 'That notification type is currently switched off.');
        }

        $sent = match ($type) {
            'deadline_reminder' => \Illuminate\Support\Facades\Artisan::call('documents:notify-deadlines'),
            default => null,
        };

        if ($sent === null) {
            return back()->with('error', 'Unknown notification type.');
        }

        return back()->with('success', 'Ran now — '.trim(\Illuminate\Support\Facades\Artisan::output()));
    }
}
