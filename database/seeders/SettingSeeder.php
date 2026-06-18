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
            'footer_text'    => '© '.date('Y').' PGC Document Tracking System',
            'allow_desktop_receive' => '0', // off by default -> staff should receive via mobile QR scan
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
