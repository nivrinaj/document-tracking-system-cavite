<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Slip — {{ $document->tracking_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 24px; color: #111; }
        .slip { width: 360px; margin: 0 auto; border: 2px dashed #999; border-radius: 12px; padding: 20px; text-align: center; }
        .org { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: .05em; }
        .title { font-size: 16px; font-weight: 700; margin: 6px 0 2px; }
        .code { font-family: monospace; font-size: 15px; font-weight: 700; margin: 4px 0 12px; }
        .qr { display: inline-block; padding: 8px; background: #fff; border: 1px solid #eee; border-radius: 8px; }
        .qr svg { width: 200px; height: 200px; }
        .meta { font-size: 12px; text-align: left; margin-top: 14px; border-top: 1px solid #eee; padding-top: 10px; }
        .meta div { margin: 2px 0; }
        .hint { font-size: 11px; color: #666; margin-top: 12px; }
        .btn { margin-top: 18px; text-align: center; }
        .btn button { padding: 8px 16px; border: none; border-radius: 6px; background: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: #fff; cursor: pointer; font-size: 14px; }
        @media print { .btn { display: none; } body { padding: 0; } .slip { border-color: #ccc; } }
    </style>
</head>
<body>
    <div class="slip">
        <div class="org">{{ $settings['organization'] ?? $settings['app_name'] ?? 'Document Tracking' }}</div>
        <div class="title">{{ $document->title }}</div>
        <div class="code">{{ $document->tracking_code }}</div>
        <div class="qr">{!! $qrSvg !!}</div>
        <div class="meta">
            <div><strong>Type:</strong> {{ $document->document_type }}</div>
            @if($document->reference_no)<div><strong>Ref No:</strong> {{ $document->reference_no }}</div>@endif
            <div><strong>Priority:</strong> {{ ucfirst($document->priority) }}</div>
            <div><strong>Assigned to:</strong> {{ $document->currentHolder?->name ?? 'Unassigned' }}</div>
            <div><strong>Released:</strong> {{ $document->released_at?->format('M d, Y') ?? '—' }}</div>
        </div>
        <div class="hint">Scan with your phone camera, log in, then tap <strong>Receive</strong>.</div>
    </div>
    <div class="btn">
        <button onclick="window.print()">🖨 Print this slip</button>
    </div>
    <script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body>
</html>
