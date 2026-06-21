<x-app-layout>
    <x-slot name="header">Report Result</x-slot>

    @php
        // Derive distributions for document-list reports so every report gets charts + stats.
        $isList = in_array($type, ['incoming','pending','completed','by_status','by_division']);
        if ($isList) {
            $statusCounts = $documents->groupBy('status')->map->count()->toArray();
            $prioCounts   = $documents->groupBy('priority')->map->count()->toArray();
            $typeCounts   = $documents->groupBy('document_type')->map->count()->toArray();
        }
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">{{ $reportTitle }}</h1>
                <p class="text-sm text-gray-400">
                    Generated {{ $generatedAt->format('M d, Y g:i A') }}
                    @if($from || $to) · {{ $from?->format('M d, Y') ?? 'start' }} → {{ $to?->format('M d, Y') ?? 'now' }} @endif
                    @if($division) · {{ $division->name }} @endif
                </p>
            </div>
            <div class="flex gap-2">
                <x-btn :href="route('reports.index')" variant="secondary">← Back</x-btn>
                <x-btn :href="route('reports.generate', array_merge(request()->query(), ['format' => 'pdf']))">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </x-btn>
            </div>
        </div>

        @if($type === 'summary')
            @php $sTotal = array_sum($byStatus); @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Total Documents" :value="$sTotal" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Open / Pending" :value="($byStatus['draft']??0)+($byStatus['released']??0)+($byStatus['received']??0)+($byStatus['forwarded']??0)" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Completed / Archived" :value="($byStatus['completed']??0)+($byStatus['archived']??0)" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Urgent + High" :value="($byPriority['urgent']??0)+($byPriority['high']??0)" color="red"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon></x-stat-card>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <x-card><h3 class="font-semibold text-sm mb-3">By Status</h3><div class="h-56"><canvas id="rStatus"></canvas></div></x-card>
                <x-card><h3 class="font-semibold text-sm mb-3">By Priority</h3><div class="h-56"><canvas id="rPriority"></canvas></div></x-card>
                <x-card><h3 class="font-semibold text-sm mb-3">By Division</h3><div class="h-56"><canvas id="rDivision"></canvas></div></x-card>
            </div>

            <x-card title="Statistics">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <div><div class="text-2xl font-bold">{{ $stats['avg_completion'] !== null ? $stats['avg_completion'] : '—' }}<span class="text-sm font-normal text-gray-400"> {{ $stats['avg_completion'] !== null ? 'days' : '' }}</span></div><div class="text-xs text-gray-500 mt-1">Avg. completion time</div></div>
                    <div><div class="text-2xl font-bold text-green-600">{{ $stats['fastest'] !== null ? $stats['fastest'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Fastest</div></div>
                    <div><div class="text-2xl font-bold text-red-600">{{ $stats['slowest'] !== null ? $stats['slowest'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Slowest</div></div>
                    <div><div class="text-2xl font-bold">{{ $stats['completed_count'] }}</div><div class="text-xs text-gray-500 mt-1">Completed</div></div>
                    <div><div class="text-2xl font-bold">{{ $stats['open_count'] }}</div><div class="text-xs text-gray-500 mt-1">Still open</div></div>
                    <div><div class="text-2xl font-bold text-amber-600">{{ $stats['avg_open_age'] !== null ? $stats['avg_open_age'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Avg. age (open)</div></div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Completion time is measured from when a document was received (or encoded) to when it was completed/archived.</p>
            </x-card>

        @elseif($type === 'sla_compliance')
            @if($slaDepartments->isEmpty())
                <x-card><p class="text-sm text-gray-500 dark:text-gray-400">No department has a processing time configured yet. Enable it in <strong>Departments → Edit → Set a completion deadline</strong>.</p></x-card>
            @else
                @php
                    $slaTotal = array_sum($slaSummary);
                    $onTimeRate = $slaTotal ? round(($slaSummary['on_time'] + $slaSummary['on_track']) / $slaTotal * 100) : 0;
                    $overdueTotal = $slaSummary['overdue'] + $slaSummary['overdue_open'];
                @endphp
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-stat-card label="Completed on time" :value="$slaSummary['on_time']" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
                    <x-stat-card label="Completed late" :value="$slaSummary['overdue']" color="red"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                    <x-stat-card label="Open, within time" :value="$slaSummary['on_track']" color="blue"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                    <x-stat-card label="Open & overdue" :value="$slaSummary['overdue_open']" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <x-card class="lg:col-span-1"><h3 class="font-semibold text-sm mb-3">On-time vs Overdue</h3><div class="h-56"><canvas id="rSla"></canvas></div></x-card>
                    <x-card class="lg:col-span-2">
                        <h3 class="font-semibold text-sm mb-3">Statistics</h3>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Documents evaluated</span><span class="font-semibold">{{ $slaTotal }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">On-time rate</span><span class="font-semibold {{ $onTimeRate >= 80 ? 'text-green-600' : 'text-amber-600' }}">{{ $onTimeRate }}%</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Avg. completion time</span><span class="font-semibold">{{ $slaStats['avg_completion'] !== null ? $slaStats['avg_completion'].' days' : '—' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Avg. days over limit</span><span class="font-semibold text-red-600">{{ $slaStats['avg_over'] !== null ? '+'.$slaStats['avg_over'].' days' : '—' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Total overdue</span><span class="font-semibold text-red-600">{{ $overdueTotal }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Worst overshoot</span><span class="font-semibold text-red-600">{{ $slaStats['worst_over'] !== null ? '+'.$slaStats['worst_over'].' days' : '—' }}</span></div>
                            <div class="col-span-2 pt-2 mt-1 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400">
                                Offices tracked: {{ $slaDepartments->map(fn($d) => $d->code.' ('.$d->sla_days.'d limit)')->join(', ') }}
                            </div>
                        </div>
                    </x-card>
                </div>
                <x-card padding="p-0">
                    <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/40"><tr>
                            <th class="table-th">Code</th><th class="table-th">Title</th><th class="table-th">Office</th><th class="table-th">Days taken</th><th class="table-th">Allowed</th><th class="table-th">Result</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php $labels=['on_time'=>['On time','green'],'overdue'=>['Overdue','red'],'on_track'=>['On track','blue'],'overdue_open'=>['Overdue (open)','amber']]; @endphp
                            @forelse($slaRows as $row)
                                <tr>
                                    <td class="table-td font-mono text-xs" data-label="Code">{{ $row['doc']->tracking_code }}</td>
                                    <td class="table-td" data-label="Title">{{ $row['doc']->title }}</td>
                                    <td class="table-td" data-label="Office">{{ $row['dept'] }}</td>
                                    <td class="table-td" data-label="Days taken">{{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }}</td>
                                    <td class="table-td" data-label="Allowed">{{ $row['sla'] }} days</td>
                                    <td class="table-td" data-label="Result"><x-badge :color="$labels[$row['status']][1]">{{ $labels[$row['status']][0] }}</x-badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">No documents match this report.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-card>
            @endif

        @elseif($type === 'staff_workload')
            <x-card><h3 class="font-semibold text-sm mb-3">Open documents per staff</h3><div class="h-64"><canvas id="rWorkload"></canvas></div></x-card>
            <x-card padding="p-0">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40"><tr><th class="table-th">Staff</th><th class="table-th">Division</th><th class="table-th">Open documents</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($workload as $row)
                            <tr><td class="table-td" data-label="Staff">{{ $row->currentHolder?->name ?? '—' }}</td><td class="table-td" data-label="Division">{{ $row->currentHolder?->division?->code ?? '—' }}</td><td class="table-td font-medium" data-label="Open documents">{{ $row->total }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400">No open documents.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>

        @else
            {{-- Document-list reports: stats + charts + table --}}
            @php $listTotal = $documents->count(); @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Total in report" :value="$listTotal" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Open / Pending" :value="($statusCounts['draft']??0)+($statusCounts['released']??0)+($statusCounts['received']??0)+($statusCounts['forwarded']??0)" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Completed / Archived" :value="($statusCounts['completed']??0)+($statusCounts['archived']??0)" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Urgent + High" :value="($prioCounts['urgent']??0)+($prioCounts['high']??0)" color="red"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon></x-stat-card>
            </div>
            @if($listTotal)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <x-card><h3 class="font-semibold text-sm mb-3">By Status</h3><div class="h-56"><canvas id="lStatus"></canvas></div></x-card>
                    <x-card><h3 class="font-semibold text-sm mb-3">By Priority</h3><div class="h-56"><canvas id="lPriority"></canvas></div></x-card>
                    <x-card><h3 class="font-semibold text-sm mb-3">By Document Type</h3><div class="h-56"><canvas id="lType"></canvas></div></x-card>
                </div>
            @endif
            <x-card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/40"><tr>
                            <th class="table-th">Code</th><th class="table-th">Title</th><th class="table-th">Type</th><th class="table-th">Priority</th><th class="table-th">Status</th><th class="table-th">Holder</th><th class="table-th">Created</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($documents as $doc)
                                <tr>
                                    <td class="table-td font-mono text-xs" data-label="Code">{{ $doc->tracking_code }}</td>
                                    <td class="table-td" data-label="Title">{{ $doc->title }}</td>
                                    <td class="table-td" data-label="Type">{{ $doc->document_type }}</td>
                                    <td class="table-td" data-label="Priority">{{ ucfirst($doc->priority) }}</td>
                                    <td class="table-td" data-label="Status">{{ \App\Models\Document::statusLabel($doc->status) }}</td>
                                    <td class="table-td" data-label="Holder">{{ $doc->currentHolder?->name ?? '—' }}</td>
                                    <td class="table-td text-xs text-gray-400" data-label="Created">{{ $doc->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No documents match this report.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($documents->isNotEmpty())<div class="px-4 py-3 text-sm text-gray-400 border-t border-gray-100 dark:border-gray-700">Total: {{ $documents->count() }} document(s)</div>@endif
            </x-card>
        @endif
    </div>

    @push('scripts')
    <script>
        (function () {
        const initCharts = () => {
            if (typeof Chart === 'undefined') return;
            const dark = document.documentElement.classList.contains('dark');
            Chart.defaults.color = dark ? '#9ca3af' : '#6b7280';
            const palette = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#ef4444','#a855f7','#14b8a6','#64748b'];
            const cap = s => s.length ? s[0].toUpperCase()+s.slice(1) : s;
            const mk = (id, obj, type='doughnut', colors=palette) => {
                const el = document.getElementById(id);
                if (!el || !obj || !Object.keys(obj).length) return;
                new Chart(el, {
                    type,
                    data: { labels: Object.keys(obj).map(cap),
                        datasets: [{ data: Object.values(obj), backgroundColor: colors, borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: type!=='bar', position: 'bottom', labels:{boxWidth:12,padding:10} } },
                        scales: type==='bar' ? { y: { beginAtZero:true, ticks:{precision:0} } } : {} }
                });
            };

            @if($type === 'summary')
                mk('rStatus', @json(\App\Models\Document::relabelStatuses($byStatus)));
                mk('rPriority', @json($byPriority), 'doughnut', ['#ef4444','#f59e0b','#0ea5e9','#94a3b8','#6366f1']);
                mk('rDivision', @json($byDivision), 'bar', '#6366f1');
            @elseif($type === 'sla_compliance' && !$slaDepartments->isEmpty())
                mk('rSla', {{ \Illuminate\Support\Js::from(['On time'=>$slaSummary['on_time'],'Completed late'=>$slaSummary['overdue'],'Open, within time'=>$slaSummary['on_track'],'Open & overdue'=>$slaSummary['overdue_open']]) }}, 'pie', ['#22c55e','#ef4444','#0ea5e9','#f59e0b']);
            @elseif($type === 'staff_workload')
                mk('rWorkload', {{ \Illuminate\Support\Js::from($workload->mapWithKeys(fn($r) => [($r->currentHolder?->name ?? '—') => $r->total])) }}, 'bar', '#6366f1');
            @elseif($isList)
                mk('lStatus', @json(\App\Models\Document::relabelStatuses($statusCounts ?? [])));
                mk('lPriority', @json($prioCounts ?? []), 'doughnut', ['#ef4444','#f59e0b','#0ea5e9','#94a3b8','#6366f1','#22c55e']);
                mk('lType', @json($typeCounts ?? []), 'bar', '#14b8a6');
            @endif
        };
        const run = () => requestAnimationFrame(initCharts);
        if (document.readyState === 'complete') run();
        else window.addEventListener('load', run);
        })();
    </script>
    @endpush
</x-app-layout>
