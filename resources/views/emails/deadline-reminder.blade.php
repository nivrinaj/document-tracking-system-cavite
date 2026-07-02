@php
    $primary = \App\Models\Setting::get('email_header_color') ?: \App\Models\Setting::get('primary_color', '#4f46e5');
    $appName = \App\Models\Setting::get('app_name', config('app.name'));
    $orgLine = \App\Models\Setting::get('email_org_line') ?: \App\Models\Setting::get('organization', '');
    $logoPath = \App\Models\Setting::get('logo_path', '');
    $showLogo = \App\Models\Setting::get('email_show_logo', '1') === '1';
    $showCta = \App\Models\Setting::get('email_show_cta', '1') === '1';
    $ctaLabel = \App\Models\Setting::get('email_cta_label', 'View My Documents');
    $footerText = \App\Models\Setting::get('email_footer_text', 'This is an automated message. Please do not reply directly to this email.');
    $showSupportLine = \App\Models\Setting::get('email_show_support_line', '1') === '1';
    $supportContact = \App\Models\Setting::get('support_contact', '');
    $loginUrl = route('documents.index');
    $greetingName = $user->first_name ?: $user->name;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">

    {{-- Header --}}
    <tr>
        <td style="background-color:{{ $primary }}; padding:28px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    @if($showLogo)
                    <td valign="middle" width="64" style="padding-right:12px;">
                        @if($logoPath)
                            <img src="{{ asset('storage/'.$logoPath) }}" alt="{{ $appName }}" height="56" style="height:56px; max-width:64px; display:block;">
                        @else
                            <span style="display:inline-block; width:56px; height:56px; line-height:56px; text-align:center; background-color:rgba(255,255,255,0.15); color:#ffffff; font-weight:700; font-size:24px; border-radius:10px;">{{ strtoupper(substr($appName, 0, 1)) }}</span>
                        @endif
                    </td>
                    @endif
                    <td valign="middle" align="{{ $showLogo ? 'right' : 'left' }}">
                        @if($orgLine)
                            <div style="color:#ffffff; font-size:14px; font-weight:600; line-height:18px;">{{ $orgLine }}</div>
                        @endif
                        <div style="color:#ffffff; font-size:12px; letter-spacing:0.3px; opacity:0.85; line-height:16px;">{{ $appName }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Title band --}}
    <tr>
        <td style="padding:32px 32px 8px 32px;">
            <h1 style="margin:0; font-size:20px; line-height:28px; color:#0f172a; font-weight:700;">Deadline Reminder</h1>
            <p style="margin:6px 0 0 0; font-size:14px; color:#64748b;">{{ now()->format('l, F j, Y') }}</p>
        </td>
    </tr>

    {{-- Greeting + intro --}}
    <tr>
        <td style="padding:16px 32px 8px 32px;">
            <p style="margin:0 0 12px 0; font-size:15px; line-height:24px; color:#334155;">Hi {{ $greetingName }},</p>
            <p style="margin:0; font-size:15px; line-height:24px; color:#334155;">
                The document(s) below are currently assigned to you and are approaching, or have already passed, their deadline. Please review and take action as soon as possible.
            </p>
        </td>
    </tr>

    {{-- Document list --}}
    <tr>
        <td style="padding:20px 32px 8px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <tr style="background-color:#f8fafc;">
                    <td style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:1px solid #e2e8f0;">Tracking Code</td>
                    <td style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:1px solid #e2e8f0;">Title</td>
                    <td style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:1px solid #e2e8f0;">Deadline</td>
                    <td style="padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:1px solid #e2e8f0;">Status</td>
                    <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0;"></td>
                </tr>
                @foreach($documents as $doc)
                    @php $highlight = $doc->deadlineHighlight(); @endphp
                    <tr>
                        <td style="padding:12px 14px; font-size:13px; color:#0f172a; font-weight:600; border-bottom:1px solid #f1f5f9;">{{ $doc->tracking_code }}</td>
                        <td style="padding:12px 14px; font-size:13px; color:#334155; border-bottom:1px solid #f1f5f9;">{{ \Illuminate\Support\Str::limit($doc->title, 34) }}</td>
                        <td style="padding:12px 14px; font-size:13px; color:#334155; border-bottom:1px solid #f1f5f9; white-space:nowrap;">{{ optional($doc->deadline)->format('M j, Y') }}</td>
                        <td style="padding:12px 14px; font-size:12px; border-bottom:1px solid #f1f5f9; white-space:nowrap;">
                            <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-weight:600; background-color:{{ $highlight['color'] ?? '#94a3b8' }}1a; color:{{ $highlight['color'] ?? '#64748b' }};">{{ $highlight['label'] ?? '—' }}</span>
                        </td>
                        <td style="padding:12px 14px; font-size:13px; border-bottom:1px solid #f1f5f9; white-space:nowrap;" align="center">
                            @if($doc->exists)
                                <a href="{{ route('documents.show', $doc->id) }}" title="Open this document" style="color:{{ $primary }}; text-decoration:none; font-weight:700;">&rarr;</a>
                            @else
                                <span style="color:#cbd5e1;">&rarr;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>

    {{-- CTA --}}
    @if($showCta)
    <tr>
        <td style="padding:28px 32px 8px 32px;" align="center">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="border-radius:8px; background-color:{{ $primary }};">
                        <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">{{ $ctaLabel }}</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    {{-- Divider --}}
    <tr>
        <td style="padding:28px 32px 0 32px;">
            <div style="border-top:1px solid #e2e8f0;"></div>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="padding:20px 32px 32px 32px;">
            <p style="margin:0 0 6px 0; font-size:12px; line-height:19px; color:#94a3b8;">
                {{ $footerText }}
            </p>
            @if($showSupportLine && $supportContact)
                <p style="margin:0; font-size:12px; line-height:19px; color:#94a3b8;">Need help? Contact {{ $supportContact }}.</p>
            @endif
        </td>
    </tr>

</table>
</td>
</tr>
</table>
</body>
</html>
