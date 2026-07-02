<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class EmailDesignController extends Controller
{
    public function edit()
    {
        return view('email-design.settings', [
            'headerColor' => Setting::get('email_header_color', ''),
            'primaryColor' => Setting::get('primary_color', '#4f46e5'),
            'orgLine' => Setting::get('email_org_line') ?: Setting::get('organization', ''),
            'ctaLabel' => Setting::get('email_cta_label', 'View My Documents'),
            'footerText' => Setting::get('email_footer_text', 'This is an automated message. Please do not reply directly to this email.'),
            'showLogo' => (bool) Setting::get('email_show_logo', '1'),
            'showCta' => (bool) Setting::get('email_show_cta', '1'),
            'showSupportLine' => (bool) Setting::get('email_show_support_line', '1'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'email_header_color' => ['nullable', 'string', 'max:20'],
            'email_org_line' => ['nullable', 'string', 'max:150'],
            'email_cta_label' => ['required', 'string', 'max:60'],
            'email_footer_text' => ['required', 'string', 'max:255'],
            'email_show_logo' => ['nullable'],
            'email_show_cta' => ['nullable'],
            'email_show_support_line' => ['nullable'],
        ]);

        $boolKeys = [
            'email_show_logo' => 'Show logo',
            'email_show_cta' => 'Show call-to-action button',
            'email_show_support_line' => 'Show support contact line',
        ];

        $changes = [];
        $old = Setting::get('email_header_color', '');
        $new = $data['email_header_color'] ?? '';
        if ($old !== $new) $changes[] = 'Header color "'.($old ?: 'theme default').'" → "'.($new ?: 'theme default').'"';

        $old = Setting::get('email_org_line', '');
        if ($old !== ($data['email_org_line'] ?? '')) $changes[] = 'Organization line "'.($old ?: '(none)').'" → "'.($data['email_org_line'] ?: '(none)').'"';

        $old = Setting::get('email_cta_label', 'View My Documents');
        if ($old !== $data['email_cta_label']) $changes[] = 'Button label "'.$old.'" → "'.$data['email_cta_label'].'"';

        $old = Setting::get('email_footer_text', '');
        if ($old !== $data['email_footer_text']) $changes[] = 'Footer text changed';

        foreach ($boolKeys as $key => $label) {
            $old = (string) Setting::get($key, '1');
            $new = $request->boolean($key) ? '1' : '0';
            if ($old !== $new) $changes[] = $label.' '.($old === '1' ? 'ON' : 'OFF').' → '.($new === '1' ? 'ON' : 'OFF');
        }

        Setting::put('email_header_color', $data['email_header_color'] ?? '');
        Setting::put('email_org_line', $data['email_org_line'] ?? '');
        Setting::put('email_cta_label', $data['email_cta_label']);
        Setting::put('email_footer_text', $data['email_footer_text']);
        foreach ($boolKeys as $key => $label) {
            Setting::put($key, $request->boolean($key) ? '1' : '0');
        }

        ActivityLog::record('email-design.settings.save', 'Email design settings'.(count($changes) ? ': '.implode('; ', $changes) : ' saved (no changes)'));

        return back()->with('success', 'Email design settings saved.');
    }
}
