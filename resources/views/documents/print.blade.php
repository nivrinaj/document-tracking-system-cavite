<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tracking Slip — {{ $document->tracking_code }}</title>
    @php
        $primary = $settings['primary_color'] ?? '#4f46e5';
        $prio = strtolower($document->priority);
        $prioColors = [
            'urgent' => '#dc2626', 'high' => '#ea580c', 'normal' => '#2563eb', 'low' => '#6b7280',
        ];
        $prioColor = $prioColors[$prio] ?? '#2563eb';
        // Origin = where it was first encoded (permanent). Department only — no division.
        $originDept = $document->creator?->department?->code ?? '—';
        $originName = $document->creator?->department?->name
            ?? trim(explode('·', (string) $document->source)[0]) ?: '—';
        $currentDept = $document->department?->code ?? '—';
    @endphp
    <style>
        /* Force background colors to print (browsers strip them by default). */
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 24px; color: #111; background: #f3f4f6; }
        .slip { width: 384px; margin: 0 auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.12); border: 1px solid #e5e7eb; }
        .header { background: {{ $primary }}; color: #fff; padding: 16px 18px 14px; text-align: center; }
        .header .logo { width: 56px; height: 56px; margin: 0 auto 8px; display: block; border-radius: 50%; background: #fff; padding: 4px; object-fit: contain; }
        .header .org { font-size: 16px; font-weight: 800; line-height: 1.2; letter-spacing: .01em; }
        .header .sub { font-size: 9px; text-transform: uppercase; letter-spacing: .12em; opacity: .85; margin-top: 3px; }
        .header .slip-label { display: inline-block; font-size: 11px; font-weight: 700; margin-top: 8px; letter-spacing: .03em; text-transform: uppercase; background: rgba(255,255,255,.18); padding: 3px 12px; border-radius: 999px; }
        .body { padding: 18px; }
        .slip-badge-wrap { text-align: center; margin-bottom: 10px; }
        .slip-badge { display: inline-block; background: {{ $primary }}; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: 5px 16px; border-radius: 999px; }
        .url { font-family: monospace; font-size: 8.5px; color: #9ca3af; word-break: break-all; text-align: center; margin-top: 10px; }
        .code-row { text-align: center; margin-bottom: 10px; }
        .code-label { font-size: 9px; text-transform: uppercase; letter-spacing: .12em; color: #9ca3af; margin-bottom: 1px; }
        .code { font-family: 'Consolas', monospace; font-size: 18px; font-weight: 700; letter-spacing: .03em; color: #111; }
        .prio { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #fff; background: {{ $prioColor }}; padding: 2px 8px; border-radius: 999px; vertical-align: middle; }
        .qr-wrap { text-align: center; margin: 4px 0 10px; }
        .qr { display: inline-block; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .qr svg { width: 140px; height: 140px; display: block; }
        .title { font-size: 14px; font-weight: 700; text-align: center; line-height: 1.3; margin-bottom: 10px; }
        .info { background: #f3f4f6; border-radius: 10px; padding: 9px 12px; border-bottom: 2px solid #d1d5db; }
        .info .row { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; font-size: 11px; }
        .info .row .k { color: #6b7280; flex-shrink: 0; }
        .info .row .v { font-weight: 600; text-align: right; color: #111; }
        .powered { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 10px; letter-spacing: .05em; }
        .btn { margin: 18px auto 0; text-align: center; }
        .btn button { padding: 9px 18px; border: none; border-radius: 8px; background: {{ $primary }}; color: #fff; cursor: pointer; font-size: 14px; }
        @media print {
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { padding: 0; background: #fff; }
            .slip { box-shadow: none; border-color: #ccc; }
            .btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="slip">
        <div class="header">
            @if(!empty($settings['logo_path']))
                <img class="logo" src="{{ asset('storage/'.$settings['logo_path']) }}" alt="">
            @endif
            <div class="org">{{ $settings['organization'] ?: 'Provincial Government of Cavite' }}</div>
            @if(!empty($settings['app_name']))<div class="sub">{{ $settings['app_name'] }}</div>@endif
        </div>
        <div class="body">
            <div class="slip-badge-wrap"><span class="slip-badge">Document Tracking Slip</span></div>
            <div class="code-row">
                <div class="code-label">Document Code</div>
                <span class="code">{{ $document->tracking_code }}</span>
                @if(\App\Models\Document::priorityEnabled())<span class="prio">{{ $prio }}</span>@endif
            </div>

            <div class="qr-wrap">
                <div class="qr">{!! $qrSvg !!}</div>
            </div>

            <div class="title">{{ $document->title }}</div>

            @php
                $sourceCombined = ($originDept && $originDept !== '—')
                    ? $originDept.' - '.$originName
                    : $originName;
            @endphp
            <div class="info">
                <div class="row"><span class="k">Type</span><span class="v">{{ $document->document_type }}</span></div>
                @if($document->voucher_number)
                    <div class="row"><span class="k">Voucher No.</span><span class="v">{{ $document->voucher_number }}</span></div>
                @endif
                @if($document->reference_no)
                    <div class="row"><span class="k">Reference No.</span><span class="v">{{ $document->reference_no }}</span></div>
                @endif
                @if($document->fund)
                    <div class="row"><span class="k">Fund</span><span class="v">{{ $document->fund->name }}</span></div>
                @endif
                @if($document->amount !== null)
                    <div class="row"><span class="k">Amount</span><span class="v">₱{{ number_format($document->amount, 2) }}</span></div>
                @endif
                @if($document->obr_no)
                    <div class="row"><span class="k">OBR No.</span><span class="v">{{ $document->obr_no }}</span></div>
                @endif
                <div class="row"><span class="k">Source / Origin</span><span class="v">{{ $sourceCombined }}</span></div>
                <div class="row"><span class="k">Encoded</span><span class="v">{{ $document->created_at->format('M d, Y g:i A') }}</span></div>
            </div>

            <div class="url">{{ $trackUrl }}</div>
            <div class="powered">Powered by PICTO</div>
        </div>
    </div>
    <div class="btn">
        <button onclick="window.print()">🖨 Print this slip</button>
    </div>
    <script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body>
</html>
