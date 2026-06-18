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

        // Super Admin can do everything.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
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
                    'footer_text'    => Setting::get('footer_text', ''),
                    'allow_desktop_receive' => Setting::get('allow_desktop_receive', '0'),
                ]);
            } catch (\Throwable $e) {
                // settings table may not exist yet (e.g. before first migrate)
                $view->with('settings', [
                    'app_name' => config('app.name'),
                    'app_short_name' => 'PGC-DTS',
                    'organization' => '',
                    'primary_color' => '#4f46e5',
                    'logo_path' => '',
                    'footer_text' => '',
                    'allow_desktop_receive' => '0',
                ]);
            }
        });
    }
}
