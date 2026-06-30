<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class QrSlipController extends Controller
{
    /** Toggle fields shown on the slip — [setting key => human label]. */
    public const FIELD_TOGGLES = [
        'qr_slip_show_type' => 'Type',
        'qr_slip_show_voucher' => 'Voucher No.',
        'qr_slip_show_reference' => 'Reference No.',
        'qr_slip_show_fund' => 'Fund',
        'qr_slip_show_amount' => 'Amount',
        'qr_slip_show_obr' => 'OBR No.',
        'qr_slip_show_source' => 'Source / Origin',
        'qr_slip_show_encoded' => 'Encoded date',
    ];

    public function edit()
    {
        return view('qr-slip.settings', [
            'headerColor' => Setting::get('qr_slip_header_color', ''),
            'badgeText' => Setting::get('qr_slip_badge_text', 'Document Tracking Slip'),
            'footerText' => Setting::get('qr_slip_footer_text', 'Powered by PICTO'),
            'showFooter' => (bool) Setting::get('qr_slip_show_footer', '1'),
            'showUrl' => (bool) Setting::get('qr_slip_show_url', '1'),
            'fieldToggles' => self::FIELD_TOGGLES,
            'fieldValues' => collect(self::FIELD_TOGGLES)->keys()
                ->mapWithKeys(fn ($key) => [$key => (bool) Setting::get($key, '1')]),
            'primaryColor' => Setting::get('primary_color', '#4f46e5'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'qr_slip_header_color' => ['nullable', 'string', 'max:20'],
            'qr_slip_badge_text' => ['required', 'string', 'max:60'],
            'qr_slip_footer_text' => ['required', 'string', 'max:80'],
            'qr_slip_show_footer' => ['nullable'],
            'qr_slip_show_url' => ['nullable'],
        ]);

        $boolKeys = array_merge(
            ['qr_slip_show_footer' => 'Show footer', 'qr_slip_show_url' => 'Show tracking URL'],
            self::FIELD_TOGGLES
        );

        $changes = [];
        $old = Setting::get('qr_slip_header_color', '');
        $new = $data['qr_slip_header_color'] ?? '';
        if ($old !== $new) $changes[] = 'Header color "'.($old ?: 'theme default').'" → "'.($new ?: 'theme default').'"';

        $old = Setting::get('qr_slip_badge_text', 'Document Tracking Slip');
        if ($old !== $data['qr_slip_badge_text']) $changes[] = 'Badge text "'.$old.'" → "'.$data['qr_slip_badge_text'].'"';

        $old = Setting::get('qr_slip_footer_text', 'Powered by PICTO');
        if ($old !== $data['qr_slip_footer_text']) $changes[] = 'Footer text "'.$old.'" → "'.$data['qr_slip_footer_text'].'"';

        foreach ($boolKeys as $key => $label) {
            $old = (string) Setting::get($key, '1');
            $new = $request->boolean($key) ? '1' : '0';
            if ($old !== $new) $changes[] = $label.' '.($old === '1' ? 'ON' : 'OFF').' → '.($new === '1' ? 'ON' : 'OFF');
        }

        Setting::put('qr_slip_header_color', $new = $data['qr_slip_header_color'] ?? '');
        Setting::put('qr_slip_badge_text', $data['qr_slip_badge_text']);
        Setting::put('qr_slip_footer_text', $data['qr_slip_footer_text']);
        foreach ($boolKeys as $key => $label) {
            Setting::put($key, $request->boolean($key) ? '1' : '0');
        }

        $desc = 'QR Slip settings'.(count($changes) ? ': '.implode('; ', $changes) : ' saved (no changes)');
        ActivityLog::record('qr-slip.settings.save', $desc);

        return back()->with('success', 'QR Slip settings saved.');
    }
}
