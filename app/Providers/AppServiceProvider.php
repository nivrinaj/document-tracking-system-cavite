<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Setting;
use App\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // When served behind an HTTPS proxy/tunnel (e.g. Cloudflare in production),
        // force generated URLs — including the QR-code links — to use https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register policies.
        Gate::policy(Document::class, DocumentPolicy::class);

        // Super Admin can do everything EXCEPT bypass the document workflow rules.
        // Document abilities run through DocumentPolicy (which already grants Super
        // Admin the right overrides) so a completed/closed document doesn't show
        // nonsensical actions.
        Gate::before(function ($user, $ability) {
            if (! $user->hasRole('Super Admin')) {
                return null;
            }
            $documentAbilities = ['view', 'receive', 'forward', 'transfer', 'pending', 'resume', 'distribute', 'archive', 'release', 'assign', 'update', 'delete', 'reopen', 'acknowledge'];

            return in_array($ability, $documentAbilities, true) ? null : true;
        });

        // Audit authentication events.
        $authContext = function () {
            $ua = request()->userAgent() ?? '';
            $device = 'Unknown device';
            if (preg_match('/\b(iPhone|iPad|iPod)\b/', $ua, $m)) $device = $m[1];
            elseif (preg_match('/Android[^;]*;\s*([^)]+)\)/', $ua, $m)) $device = 'Android (' . trim(explode(' Build', $m[1])[0]) . ')';
            elseif (str_contains($ua, 'Macintosh')) $device = 'Mac';
            elseif (str_contains($ua, 'Windows')) $device = 'Windows PC';
            elseif (str_contains($ua, 'Linux')) $device = 'Linux';

            $browser = 'Unknown browser';
            if (str_contains($ua, 'Edg/')) $browser = 'Edge';
            elseif (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) $browser = 'Opera';
            elseif (str_contains($ua, 'Chrome/') && !str_contains($ua, 'Edg/')) $browser = 'Chrome';
            elseif (str_contains($ua, 'Firefox/')) $browser = 'Firefox';
            elseif (str_contains($ua, 'Safari/') && !str_contains($ua, 'Chrome')) $browser = 'Safari';

            $parts = [$device . ' / ' . $browser];
            $location = \App\Models\ActivityLog::resolveLocation(request()->ip());
            if ($location) $parts[] = $location;

            return implode(' — ', $parts);
        };

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) use ($authContext) {
            \App\Models\ActivityLog::record('login', 'Logged in — ' . $authContext(), null, $event->user->id);
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) use ($authContext) {
            if ($event->user) {
                \App\Models\ActivityLog::record('logout', 'Logged out — ' . $authContext(), null, $event->user->id);
            }
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) use ($authContext) {
            $who = $event->credentials['username'] ?? $event->credentials['email'] ?? 'unknown';
            \App\Models\ActivityLog::record('login.failed', "Failed login attempt for \"{$who}\" — " . $authContext(), null, null);
        });

        // Make system settings (logo, colors, app name) available to every view as $settings.
        View::composer('*', function ($view) {
            try {
                $view->with('settings', [
                    'app_name'       => Setting::get('app_name', config('app.name')),
                    'app_short_name' => Setting::get('app_short_name', 'PGC-DTS'),
                    'organization'   => Setting::get('organization', ''),
                    'primary_color'  => Setting::get('primary_color', '#4f46e5'),
                    'logo_path'      => Setting::get('logo_path', ''),
                    'favicon_path'   => Setting::get('favicon_path', ''),
                    'login_bg_path'  => Setting::get('login_bg_path', ''),
                    'footer_text'    => Setting::get('footer_text', ''),
                    'allow_desktop_receive' => Setting::get('allow_desktop_receive', '0'),
                    'allow_cross_department' => Setting::get('allow_cross_department', '0'),
                    'enable_priority' => Setting::get('enable_priority', '0'),
                    'enable_route_items' => Setting::get('enable_route_items', '0'),
                    'enable_batch_receive' => Setting::get('enable_batch_receive', '1'),
                    'enable_document_linking' => Setting::get('enable_document_linking', '1'),
                    'enable_attachments' => Setting::get('enable_attachments', '0'),
                    'enable_digital_copy' => Setting::get('enable_digital_copy', '0'),
                    'enable_messaging' => Setting::get('enable_messaging', '0'),
                    'messaging_scope' => Setting::get('messaging_scope', 'all'),
                    'messaging_excluded_roles' => Setting::get('messaging_excluded_roles', '[]'),
                    'tracking_prefix' => Setting::get('tracking_prefix', 'PGC'),
                    'records_per_page' => Setting::get('records_per_page', '12'),
                    'support_contact' => Setting::get('support_contact', ''),
                    'announcement'   => Setting::get('announcement', ''),
                ]);
            } catch (\Throwable $e) {
                // settings table may not exist yet (e.g. before first migrate)
                $view->with('settings', [
                    'app_name' => config('app.name'),
                    'app_short_name' => 'PGC-DTS',
                    'organization' => '',
                    'primary_color' => '#4f46e5',
                    'logo_path' => '',
                    'favicon_path' => '',
                    'login_bg_path' => '',
                    'footer_text' => '',
                    'allow_desktop_receive' => '0',
                    'allow_cross_department' => '0',
                    'enable_priority' => '0',
                    'enable_route_items' => '0',
                    'enable_batch_receive' => '1',
                    'enable_document_linking' => '1',
                    'enable_attachments' => '0',
                    'enable_digital_copy' => '0',
                    'enable_messaging' => '0',
                    'messaging_scope' => 'all',
                    'messaging_excluded_roles' => '[]',
                    'tracking_prefix' => 'PGC',
                    'records_per_page' => '12',
                    'support_contact' => '',
                    'announcement' => '',
                ]);
            }
        });
    }
}
