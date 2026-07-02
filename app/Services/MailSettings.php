<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * Applies the Super-Admin-configured SMTP settings (Notification Settings
 * GUI) to Laravel's runtime mail config. Until "mail_enabled" is turned on,
 * the app stays on whatever .env's MAIL_MAILER says (normally "log") — so a
 * half-configured mail setup never accidentally starts sending real email.
 */
class MailSettings
{
    public static function apply(): void
    {
        if (Setting::get('mail_enabled', '0') !== '1') {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => Setting::get('mail_host', ''),
            'mail.mailers.smtp.port' => (int) Setting::get('mail_port', 587),
            'mail.mailers.smtp.encryption' => self::encryption(),
            'mail.mailers.smtp.username' => Setting::get('mail_username', ''),
            'mail.mailers.smtp.password' => self::password(),
            'mail.from.address' => Setting::get('mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => Setting::get('mail_from_name') ?: config('mail.from.name'),
        ]);
    }

    private static function encryption(): ?string
    {
        $value = Setting::get('mail_encryption', 'tls');

        return $value !== '' ? $value : null;
    }

    /** The SMTP password, decrypted — stored encrypted at rest since, unlike other Settings, this is a real credential. */
    public static function password(): string
    {
        $stored = (string) Setting::get('mail_password', '');
        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Encrypt and store a new SMTP password. Pass an empty string to clear it. */
    public static function setPassword(string $plain): void
    {
        Setting::put('mail_password', $plain === '' ? '' : Crypt::encryptString($plain));
    }
}
