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
        .header { background: {{ $primary }}; color: #fff; padding: 14px 18px; text-align: center; }
        .header .org { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; opacity: .85; }
        .header .slip-label { font-size: 15px; font-weight: 700; margin-top: 2px; letter-spacing: .02em; }
        .body { padding: 18px; }
        .code-row { text-align: center; margin-bottom: 12px; }
        .code { font-family: 'Consolas', monospace; font-size: 18px; font-weight: 700; letter-spacing: .03em; color: #111; }
        .prio { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #fff; background: {{ $prioColor }}; padding: 2px 8px; border-radius: 999px; vertical-align: middle; }
        .qr-wrap { text-align: center; margin: 6px 0 14px; }
        .qr { display: inline-block; padding: 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .qr svg { width: 200px; height: 200px; display: block; }
        .title { font-size: 15px; font-weight: 700; text-align: center; line-height: 1.3; margin-bottom: 14px; }
        .meta { font-size: 12px; border-top: 1px dashed #d1d5db; padding-top: 12px; }
        .meta-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; }
        .meta-row .k { color: #6b7280; flex-shrink: 0; }
        .meta-row .v { font-weight: 600; text-align: right; color: #111; }
        .route { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 12px 0; padding: 10px; background: #f9fafb; border-radius: 10px; font-size: 12px; }
        .route .box { text-align: center; flex: 1; }
        .route .box .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; }
        .route .box .val { font-weight: 700; margin-top: 1px; }
        .route .box .asof { font-size: 8px; color: #9ca3af; margin-top: 2px; }
        .route .arrow { color: {{ $primary }}; font-size: 18px; font-weight: 700; }
        .hint { font-size: 11px; color: #4b5563; text-align: center; margin-top: 14px; line-height: 1.45; }
        .hint strong { color: #111; }
        .url { font-family: monospace; font-size: 9px; color: #9ca3af; word-break: break-all; text-align: center; margin-top: 6px; }
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
            <div class="org">{{ $settings['organization'] ?? $settings['app_name'] ?? 'Provincial Government of Cavite' }}</div>
            <div class="slip-label">Document Tracking Slip</div>
        </div>
        <div class="body">
            <div class="code-row">
                <span class="code">{{ $document->tracking_code }}</span>
                <span class="prio">{{ $prio }}</span>
            </div>

            <div class="qr-wrap">
                <div class="qr">{!! $qrSvg !!}</div>
            </div>

            <div class="title">{{ $document->title }}</div>

            {{-- Origin only (permanent). Current location lives on the QR, never on paper. --}}
            <div class="route">
                <div class="box" style="flex: 1;">
                    <div class="lbl">Origin Office</div>
                    <div class="val">{{ $originDept }}</div>
                </div>
            </div>

            <div class="meta">
                <div class="meta-row"><span class="k">Type</span><span class="v">{{ $document->document_type }}</span></div>
                @if($document->voucher_number)
                    <div class="meta-row"><span class="k">Voucher No.</span><span class="v">{{ $document->voucher_number }}</span></div>
                @endif
                @if($document->reference_no)
                    <div class="meta-row"><span class="k">Reference No.</span><span class="v">{{ $document->reference_no }}</span></div>
                @endif
                <div class="meta-row"><span class="k">Source / Origin</span><span class="v">{{ $originName }}</span></div>
                <div class="meta-row"><span class="k">Encoded</span><span class="v">{{ $document->created_at->format('M d, Y g:i A') }}</span></div>
            </div>

            <div class="hint">
                📱 <strong>Scan this QR</strong> to see the <strong>live current holder, office &amp; full history</strong>, then tap <strong>Receive / Claim</strong>.<br>
                <span style="color:#9ca3af;">Print once — this slip stays valid for the document's entire life, no matter how many offices it passes through.</span>
            </div>
            <div class="url">{{ $trackUrl }}</div>
        </div>
    </div>
    <div class="btn">
        <button onclick="window.print()">🖨 Print this slip</button>
    </div>
    <script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body>
</html>
