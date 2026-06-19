<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #222; margin: 0; }
        .header { border-bottom: 2px solid {{ $settings['primary_color'] ?? '#4f46e5' }}; padding-bottom: 8px; margin-bottom: 14px; }
        .org { font-size: 13px; font-weight: bold; }
        .app { font-size: 10px; color: #666; }
        h1 { font-size: 15px; margin: 4px 0; }
        .meta { font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th { background: #f1f1f4; text-align: left; padding: 6px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #ddd; }
        td { padding: 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        .summary-grid { width: 100%; }
        .summary-grid td { vertical-align: top; width: 33%; border: none; padding: 0 8px; }
        .box { border: 1px solid #ddd; border-radius: 6px; padding: 8px; }
        .box h3 { margin: 0 0 6px; font-size: 11px; }
        .row { display: flex; justify-content: space-between; font-size: 10px; padding: 2px 0; }
        .footer { margin-top: 18px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="org">{{ $org ?: $appName }}</div>
        <div class="app">{{ $appName }}</div>
        <h1>{{ $reportTitle }}</h1>
        <div class="meta">
            Generated: {{ $generatedAt->format('M d, Y g:i A') }}
            @if($from || $to) &nbsp;|&nbsp; Period: {{ $from?->format('M d, Y') ?? 'start' }} to {{ $to?->format('M d, Y') ?? 'now' }} @endif
            @if($division) &nbsp;|&nbsp; Division: {{ $division->name }} @endif
        </div>
    </div>

    @if($type === 'summary')
        <table class="summary-grid"><tr>
            <td><div class="box"><h3>By Status</h3>@foreach($byStatus as $k => $v)<div class="row"><span>{{ ucfirst($k) }}</span><strong>{{ $v }}</strong></div>@endforeach</div></td>
            <td><div class="box"><h3>By Priority</h3>@foreach($byPriority as $k => $v)<div class="row"><span>{{ ucfirst($k) }}</span><strong>{{ $v }}</strong></div>@endforeach</div></td>
            <td><div class="box"><h3>By Division</h3>@foreach($byDivision as $k => $v)<div class="row"><span>{{ $k }}</span><strong>{{ $v }}</strong></div>@endforeach</div></td>
        </tr></table>
    @elseif($type === 'sla_compliance')
        @php $labels=['on_time'=>'On time','overdue'=>'Overdue','on_track'=>'On track','overdue_open'=>'Overdue (open)']; @endphp
        <p style="margin:0 0 8px;font-size:11px;">
            On time: <strong>{{ $slaSummary['on_time'] }}</strong> &nbsp;|&nbsp;
            Completed overdue: <strong>{{ $slaSummary['overdue'] }}</strong> &nbsp;|&nbsp;
            Open within SLA: <strong>{{ $slaSummary['on_track'] }}</strong> &nbsp;|&nbsp;
            Open &amp; overdue: <strong>{{ $slaSummary['overdue_open'] }}</strong>
        </p>
        <table>
            <thead><tr><th>Code</th><th>Title</th><th>Dept</th><th>Days</th><th>SLA</th><th>Result</th></tr></thead>
            <tbody>
                @forelse($slaRows as $row)
                    <tr><td>{{ $row['doc']->tracking_code }}</td><td>{{ $row['doc']->title }}</td><td>{{ $row['dept'] }}</td><td>{{ $row['days'] }}</td><td>{{ $row['sla'] }}</td><td>{{ $labels[$row['status']] }}</td></tr>
                @empty
                    <tr><td colspan="6">No documents match the SLA criteria.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($type === 'staff_workload')
        <table>
            <thead><tr><th>Staff</th><th>Division</th><th>Open Documents</th></tr></thead>
            <tbody>
                @forelse($workload as $row)
                    <tr><td>{{ $row->currentHolder?->name ?? '—' }}</td><td>{{ $row->currentHolder?->division?->code ?? '—' }}</td><td>{{ $row->total }}</td></tr>
                @empty
                    <tr><td colspan="3">No open documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead><tr><th>Code</th><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Holder</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->tracking_code }}</td><td>{{ $doc->title }}</td><td>{{ $doc->document_type }}</td>
                        <td>{{ ucfirst($doc->priority) }}</td><td>{{ ucfirst($doc->status) }}</td>
                        <td>{{ $doc->currentHolder?->name ?? '—' }}</td><td>{{ $doc->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No documents match this report.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p style="margin-top:8px;font-size:10px;">Total: {{ $documents->count() }} document(s)</p>
    @endif

    <div class="footer">{{ $settings['footer_text'] ?? '' }} — Printed by {{ auth()->user()->name }}</div>
</body>
</html>
