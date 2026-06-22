<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'app_name'       => 'PGC Document Tracking System',
            'app_short_name' => 'PGC-DTS',
            'organization'   => 'Provincial Government Center',
            'primary_color'  => '#4f46e5',   // indigo-600
            'logo_path'      => '',          // relative path in storage, set via Settings UI
            'favicon_path'   => '',          // browser tab icon
            'login_bg_path'  => '',          // login page background image
            'footer_text'    => '© '.date('Y').' PGC Document Tracking System',
            'allow_desktop_receive' => '0', // off by default -> staff should receive via mobile QR scan
            'allow_cross_department' => '0', // off -> can only route within own department
            'enable_priority' => '0',       // off -> priority field hidden everywhere
            'tracking_prefix' => 'PGC',     // prefix for tracking codes: {PREFIX}-2026-XXXXX
            'records_per_page' => '12',      // pagination size for tables
            'support_contact' => '',         // e.g. "ISDA Help Desk · local 1234" shown in footer
            'announcement'   => '',          // optional banner shown on the dashboard
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
