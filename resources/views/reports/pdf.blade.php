<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php
        $primary = $settings['primary_color'] ?? '#4f46e5';
        $palette = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#ef4444','#a855f7','#14b8a6','#64748b'];

        // Pie rendered as a PNG via GD — embeds reliably in DomPDF (inline SVG does not).
        $svgPie = function (array $data, array $colors) {
            if (array_sum(array_filter($data, fn ($v) => $v > 0)) <= 0) return '<div style="font-size:10px;color:#999;">No data.</div>';
            return '<img src="'.\App\Support\ChartImage::pie($data, array_values($colors)).'" width="132" height="132" style="display:block;">';
        };
        $legend = function (array $data, array $colors) {
            $total = max(1, array_sum($data)); $out = ''; $i = 0;
            foreach ($data as $label => $val) {
                $color = $colors[$i % count($colors)];
                $pct = round($val / $total * 100);
                $out .= '<div class="lg-row"><span class="sw" style="background:'.$color.'"></span>'
                      . '<span class="lg-label">'.e(ucfirst($label)).'</span>'
                      . '<span class="lg-val">'.$val.' ('.$pct.'%)</span></div>';
                $i++;
            }
            return $out;
        };
        $bars = function (array $data, array $colors) {
            if (empty($data)) return '<div style="font-size:10px;color:#999;">No data.</div>';
            $max = max(1, max($data)); $out = ''; $i = 0;
            foreach ($data as $label => $val) {
                $w = round($val / $max * 100); $color = $colors[$i % count($colors)];
                $out .= '<div class="bar-row"><span class="bar-label">'.e(ucfirst($label)).'</span>'
                      . '<span class="bar-track"><span class="bar-fill" style="width:'.$w.'%;background:'.$color.';">&nbsp;</span></span>'
                      . '<span class="bar-val">'.$val.'</span></div>';
                $i++;
            }
            return $out;
        };

        $prio = \App\Models\Document::priorityEnabled();
        // Distributions for document-list reports.
        $isList = in_array($type, ['incoming','pending','completed','by_status','by_division']);
        if ($isList) {
            $statusCounts = $documents->groupBy('status')->map->count()->toArray();
            $prioCounts   = $documents->groupBy('priority')->map->count()->toArray();
            $divCounts    = $documents->groupBy(fn ($d) => $d->division?->code ?? 'Unassigned')->map->count()->toArray();
        }
    @endphp
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #222; margin: 0; }
        .header { border-bottom: 3px solid {{ $primary }}; padding-bottom: 8px; margin-bottom: 14px; }
        .org { font-size: 14px; font-weight: bold; color: {{ $primary }}; }
        .app { font-size: 10px; color: #666; }
        h1 { font-size: 15px; margin: 4px 0; }
        h3 { margin: 0 0 6px; font-size: 11px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 12px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th { background: {{ $primary }}; color: #fff; text-align: left; padding: 6px; font-size: 9px; text-transform: uppercase; }
        table.data td { padding: 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.data tr:nth-child(even) td { background: #f8f8fb; }
        /* Stat cards */
        .stats { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 12px; }
        .stats td { width: 25%; }
        .stat { border-radius: 6px; padding: 8px 10px; color: #fff; }
        .stat .n { font-size: 18px; font-weight: bold; }
        .stat .l { font-size: 9px; opacity: .9; }
        /* Chart layout */
        .chart-wrap { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .chart-wrap td { vertical-align: top; padding: 6px; }
        .panel { border: 1px solid #e3e3ea; border-radius: 8px; padding: 10px; }
        .lg-row { font-size: 10px; padding: 2px 0; }
        .sw { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: 5px; }
        .lg-label { display: inline-block; }
        .lg-val { float: right; color: #555; }
        .bar-row { margin: 3px 0; font-size: 9px; }
        .bar-label { display: inline-block; width: 80px; }
        .bar-track { display: inline-block; width: 60%; background: #eef0f4; border-radius: 4px; vertical-align: middle; }
        .bar-fill { display: inline-block; height: 9px; border-radius: 4px; }
        .bar-val { font-weight: bold; margin-left: 4px; }
        .badge { padding: 2px 7px; border-radius: 999px; color: #fff; font-size: 9px; }
        .footer { margin-top: 18px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 6px; }
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
        @php
            $sTotal = array_sum($byStatus);
            $sOpen = ($byStatus['draft']??0)+($byStatus['released']??0)+($byStatus['received']??0)+($byStatus['forwarded']??0);
            $sPending = $pendingCount ?? 0;
            $sActive = max(0, $sOpen - $sPending);
        @endphp
        <table class="stats"><tr>
            <td><div class="stat" style="background:{{ $primary }}"><div class="n">{{ $sTotal }}</div><div class="l">Total documents</div></div></td>
            <td><div class="stat" style="background:#0ea5e9"><div class="n">{{ $sActive }}</div><div class="l">Active (ongoing)</div></div></td>
            <td><div class="stat" style="background:#f59e0b"><div class="n">{{ $sPending }}</div><div class="l">Pending (paused)</div></div></td>
            <td><div class="stat" style="background:#22c55e"><div class="n">{{ ($byStatus['completed']??0)+($byStatus['archived']??0) }}</div><div class="l">Completed / Archived</div></div></td>
        </tr></table>

        <table class="chart-wrap"><tr>
            <td style="width:50%;"><div class="panel"><h3>By Status</h3>
                <table style="width:100%"><tr>
                    <td style="width:140px;">{!! $svgPie(\App\Models\Document::relabelStatuses($byStatus), $palette) !!}</td>
                    <td>{!! $legend(\App\Models\Document::relabelStatuses($byStatus), $palette) !!}</td>
                </tr></table>
            </div></td>
            @if($prio)
            <td style="width:50%;"><div class="panel"><h3>By Priority</h3>
                @php $prioColors=['#ef4444','#f59e0b','#0ea5e9','#94a3b8','#6366f1']; @endphp
                <table style="width:100%"><tr>
                    <td style="width:140px;">{!! $svgPie($byPriority, $prioColors) !!}</td>
                    <td>{!! $legend($byPriority, $prioColors) !!}</td>
                </tr></table>
            </div></td>
            @endif
        </tr></table>
        <div class="panel"><h3>By Division</h3>{!! $bars($byDivision, $palette) !!}</div>

        <div class="panel" style="margin-top:10px;"><h3>Statistics</h3>
            <table style="width:100%"><tr style="text-align:center;">
                <td><div style="font-size:16px;font-weight:bold;">{{ $stats['avg_completion'] !== null ? $stats['avg_completion'].' d' : '—' }}</div><div style="font-size:9px;color:#777;">Avg. completion</div></td>
                <td><div style="font-size:16px;font-weight:bold;color:#22c55e;">{{ $stats['fastest'] !== null ? $stats['fastest'].' d' : '—' }}</div><div style="font-size:9px;color:#777;">Fastest</div></td>
                <td><div style="font-size:16px;font-weight:bold;color:#ef4444;">{{ $stats['slowest'] !== null ? $stats['slowest'].' d' : '—' }}</div><div style="font-size:9px;color:#777;">Slowest</div></td>
                <td><div style="font-size:16px;font-weight:bold;">{{ $stats['completed_count'] }}</div><div style="font-size:9px;color:#777;">Completed</div></td>
                <td><div style="font-size:16px;font-weight:bold;">{{ $stats['open_count'] }}</div><div style="font-size:9px;color:#777;">Still open</div></td>
                <td><div style="font-size:16px;font-weight:bold;color:#f59e0b;">{{ $stats['avg_open_age'] !== null ? $stats['avg_open_age'].' d' : '—' }}</div><div style="font-size:9px;color:#777;">Avg. age (open)</div></td>
            </tr></table>
            <div style="font-size:8px;color:#999;margin-top:4px;">Completion time = received (or encoded) → completed/archived.</div>
        </div>

    @elseif($type === 'aging')
        @php $as = $agingStats; $bk = $as['buckets']; @endphp
        <table class="stats"><tr>
            <td><div class="stat" style="background:{{ $primary }}"><div class="n">{{ $as['count'] }}</div><div class="l">Active documents</div></div></td>
            <td><div class="stat" style="background:#ef4444"><div class="n" style="font-size:13px;">{{ $as['oldest'] ? $as['oldest']->totalTime() : '—' }}</div><div class="l">Oldest document</div></div></td>
            <td><div class="stat" style="background:#f59e0b"><div class="n" style="font-size:13px;">{{ $as['avg_holder'] !== null ? \App\Models\Document::humanDuration($as['avg_holder']) : '—' }}</div><div class="l">Avg. time w/ holder</div></div></td>
            <td><div class="stat" style="background:#ef4444"><div class="n" style="font-size:13px;">{{ $as['longest_holder'] !== null ? \App\Models\Document::humanDuration($as['longest_holder']) : '—' }}</div><div class="l">Longest w/ a holder</div></div></td>
        </tr></table>

        <div class="panel" style="margin-bottom:10px;"><h3>How long with the current holder</h3>
            @php $bkMeta = ['under_1h'=>['Under 1 hour','#22c55e'],'h1_8'=>['1–8 hours','#0ea5e9'],'h8_24'=>['8–24 hours','#f59e0b'],'d1_3'=>['1–3 days','#ea580c'],'over_3d'=>['Over 3 days','#ef4444']]; @endphp
            <table style="width:100%;text-align:center;"><tr>
                @foreach($bkMeta as $key => [$label, $color])
                    <td style="width:20%;"><div style="font-size:18px;font-weight:bold;color:{{ $color }};">{{ $bk[$key] }}</div><div style="font-size:9px;color:#777;">{{ $label }}</div></td>
                @endforeach
            </tr></table>
        </div>
        <table class="data">
            <thead><tr><th>#</th><th>Code</th><th>Title</th><th>Total time</th><th>Currently with</th><th>Time w/ holder</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($aging as $i => $doc)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $doc->tracking_code }}</td>
                        <td>{{ $doc->title }}</td>
                        <td>{{ $doc->totalTime() }}</td>
                        <td>{{ $doc->possessorLabel() }}</td>
                        <td>{{ $doc->timeWithCurrentHolder() }}</td>
                        <td>{{ \App\Models\Document::statusLabel($doc->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No open documents — nothing is aging.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p style="margin-top:8px;font-size:9px;color:#777;">Oldest first. Pending documents are excluded. Total: {{ $aging->count() }} document(s).</p>

    @elseif($type === 'sla_compliance')
        @php
            $labels=['on_time'=>'On time','overdue'=>'Completed late','on_track'=>'Open, within time','overdue_open'=>'Open & overdue'];
            $badgeColors=['on_time'=>'#22c55e','overdue'=>'#ef4444','on_track'=>'#0ea5e9','overdue_open'=>'#f59e0b'];
            $slaTotal = array_sum($slaSummary);
            $onTimeRate = $slaTotal ? round(($slaSummary['on_time']+$slaSummary['on_track'])/$slaTotal*100) : 0;
            $pieData = ['On time'=>$slaSummary['on_time'],'Completed late'=>$slaSummary['overdue'],'Open, within time'=>$slaSummary['on_track'],'Open & overdue'=>$slaSummary['overdue_open']];
            $pieColors = ['#22c55e','#ef4444','#0ea5e9','#f59e0b'];
        @endphp
        <table class="stats"><tr>
            <td><div class="stat" style="background:#22c55e"><div class="n">{{ $slaSummary['on_time'] }}</div><div class="l">Completed on time</div></div></td>
            <td><div class="stat" style="background:#ef4444"><div class="n">{{ $slaSummary['overdue'] }}</div><div class="l">Completed late</div></div></td>
            <td><div class="stat" style="background:#0ea5e9"><div class="n">{{ $slaSummary['on_track'] }}</div><div class="l">Open, within time</div></div></td>
            <td><div class="stat" style="background:#f59e0b"><div class="n">{{ $slaSummary['overdue_open'] }}</div><div class="l">Open &amp; overdue</div></div></td>
        </tr></table>

        <table class="chart-wrap"><tr>
            <td style="width:46%;"><div class="panel"><h3>On-time vs Overdue</h3>
                <table style="width:100%"><tr>
                    <td style="width:140px;">{!! $svgPie($pieData, $pieColors) !!}</td>
                    <td>{!! $legend($pieData, $pieColors) !!}</td>
                </tr></table>
            </div></td>
            <td style="width:54%;"><div class="panel"><h3>Statistics</h3>
                <div class="lg-row">Documents evaluated <span class="lg-val">{{ $slaTotal }}</span></div>
                <div class="lg-row">On-time rate <span class="lg-val">{{ $onTimeRate }}%</span></div>
                <div class="lg-row">Avg. completion time <span class="lg-val">{{ $slaStats['avg_completion'] !== null ? $slaStats['avg_completion'].' days' : '—' }}</span></div>
                <div class="lg-row">Avg. days over limit <span class="lg-val">{{ $slaStats['avg_over'] !== null ? '+'.$slaStats['avg_over'].' days' : '—' }}</span></div>
                <div class="lg-row">Total overdue <span class="lg-val">{{ $slaSummary['overdue'] + $slaSummary['overdue_open'] }}</span></div>
                <div class="lg-row">Worst overshoot <span class="lg-val">{{ $slaStats['worst_over'] !== null ? '+'.$slaStats['worst_over'].' days' : '—' }}</span></div>
                <div class="lg-row" style="border-top:1px solid #eee;margin-top:4px;padding-top:4px;color:#777;">
                    Offices tracked: {{ $slaDepartments->map(fn($d) => $d->code.' ('.$d->sla_days.'d limit)')->join(', ') }}
                </div>
            </div></td>
        </tr></table>

        <table class="data">
            <thead><tr><th>Code</th><th>Title</th><th>Office</th><th>Days taken</th><th>Allowed</th><th>Result</th></tr></thead>
            <tbody>
                @forelse($slaRows as $row)
                    <tr><td>{{ $row['doc']->tracking_code }}</td><td>{{ $row['doc']->title }}</td><td>{{ $row['dept'] }}</td><td>{{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }}</td><td>{{ $row['sla'] }} days</td>
                        <td><span class="badge" style="background:{{ $badgeColors[$row['status']] }}">{{ $labels[$row['status']] }}</span></td></tr>
                @empty
                    <tr><td colspan="6">No documents match this report.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif($type === 'staff_workload')
        @php $wl = $workload->mapWithKeys(fn($r) => [($r->currentHolder?->name ?? '—') => $r->total])->toArray(); @endphp
        <div class="panel"><h3>Open documents currently held — per staff member</h3>{!! $bars($wl, $palette) !!}</div>
        <table class="data">
            <thead><tr><th>#</th><th>Staff member</th><th>Office &middot; Division</th><th>Open documents held</th></tr></thead>
            <tbody>
                @forelse($workload as $i => $row)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $row->currentHolder?->name ?? '—' }}</td><td>{{ $row->currentHolder?->orgShort() ?? '—' }}</td><td>{{ $row->total }}</td></tr>
                @empty
                    <tr><td colspan="4">No open documents are currently held by anyone.</td></tr>
                @endforelse
            </tbody>
        </table>

    @else
        @php
            $listTotal = $documents->count();
            $listOpen = ($statusCounts['draft']??0)+($statusCounts['released']??0)+($statusCounts['received']??0)+($statusCounts['forwarded']??0);
            $listPending = $documents->where('is_pending', true)->count();
            $listActive = max(0, $listOpen - $listPending);
        @endphp
        <table class="stats"><tr>
            <td><div class="stat" style="background:{{ $primary }}"><div class="n">{{ $listTotal }}</div><div class="l">Total documents</div></div></td>
            <td><div class="stat" style="background:#0ea5e9"><div class="n">{{ $listActive }}</div><div class="l">Active (ongoing)</div></div></td>
            <td><div class="stat" style="background:#f59e0b"><div class="n">{{ $listPending }}</div><div class="l">Pending (paused)</div></div></td>
            <td><div class="stat" style="background:#22c55e"><div class="n">{{ ($statusCounts['completed']??0)+($statusCounts['archived']??0) }}</div><div class="l">Completed / Archived</div></div></td>
        </tr></table>

        @if($listTotal)
            <table class="chart-wrap"><tr>
                <td style="width:50%;"><div class="panel"><h3>By Status</h3>
                    <table style="width:100%"><tr><td style="width:140px;">{!! $svgPie(\App\Models\Document::relabelStatuses($statusCounts), $palette) !!}</td><td>{!! $legend(\App\Models\Document::relabelStatuses($statusCounts), $palette) !!}</td></tr></table>
                </div></td>
                @if($prio)
                <td style="width:50%;"><div class="panel"><h3>By Priority</h3>
                    @php $prioColors=['#ef4444','#f59e0b','#0ea5e9','#94a3b8','#6366f1','#22c55e']; @endphp
                    <table style="width:100%"><tr><td style="width:140px;">{!! $svgPie($prioCounts, $prioColors) !!}</td><td>{!! $legend($prioCounts, $prioColors) !!}</td></tr></table>
                </div></td>
                @endif
            </tr></table>
            @if($type === 'by_division')
                <div class="panel" style="margin-top:10px;"><h3>By Division</h3>{!! $bars($divCounts, $palette) !!}</div>
            @endif
        @endif

        <table class="data">
            <thead><tr><th>Code</th><th>Title</th><th>Type</th><th>Division</th>@if($prio)<th>Priority</th>@endif<th>Status</th><th>Holder</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->tracking_code }}</td><td>{{ $doc->title }}</td><td>{{ $doc->document_type }}</td>
                        <td>{{ $doc->division?->code ?? '—' }}</td>
                        @if($prio)<td>{{ ucfirst($doc->priority) }}</td>@endif<td>{{ \App\Models\Document::statusLabel($doc->status) }}</td>
                        <td>{{ $doc->currentHolder?->name ?? '—' }}</td><td>{{ $doc->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $prio ? 8 : 7 }}">No documents match this report.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p style="margin-top:8px;font-size:10px;">Total: {{ $documents->count() }} document(s)</p>
    @endif

    <div class="footer">{{ $settings['footer_text'] ?? '' }} — Printed by {{ auth()->user()->name }} on {{ now()->format('M d, Y g:i A') }}</div>
</body>
</html>
