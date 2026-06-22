<x-app-layout>
    <x-slot name="header">Report Result</x-slot>

    @php
        $prio = \App\Models\Document::priorityEnabled();
        // Derive distributions for document-list reports so every report gets charts + stats.
        $isList = in_array($type, ['incoming','pending','completed','by_status','by_division']);
        if ($isList) {
            $statusCounts = $documents->groupBy('status')->map->count()->toArray();
            $prioCounts   = $documents->groupBy('priority')->map->count()->toArray();
            $typeCounts   = $documents->groupBy('document_type')->map->count()->toArray();
            $divCounts    = $documents->groupBy(fn ($d) => $d->division?->code ?? 'Unassigned')->map->count()->toArray();
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
            @php
                $sTotal = array_sum($byStatus);
                $sOpen = ($byStatus['draft']??0)+($byStatus['released']??0)+($byStatus['received']??0)+($byStatus['forwarded']??0);
                $sPending = $pendingCount ?? 0;
                $sActive = max(0, $sOpen - $sPending);
                $sDone = ($byStatus['completed']??0)+($byStatus['archived']??0);
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Total documents" :value="$sTotal" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Active (ongoing)" :value="$sActive" color="blue"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Pending (paused)" :value="$sPending" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Completed / Archived" :value="$sDone" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <x-card><h3 class="font-semibold text-sm mb-3">By Status</h3><div class="h-56"><canvas id="rStatus"></canvas></div></x-card>
                @if($prio)<x-card><h3 class="font-semibold text-sm mb-3">By Priority</h3><div class="h-56"><canvas id="rPriority"></canvas></div></x-card>@endif
                <x-card><h3 class="font-semibold text-sm mb-3">By Division</h3><div class="h-56"><canvas id="rDivision"></canvas></div></x-card>
            </div>

            <x-card title="Statistics">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <div><div class="text-2xl font-bold">{{ $stats['avg_completion'] !== null ? $stats['avg_completion'] : '—' }}<span class="text-sm font-normal text-gray-400"> {{ $stats['avg_completion'] !== null ? 'days' : '' }}</span></div><div class="text-xs text-gray-500 mt-1">Avg. completion time</div></div>
                    <div><div class="text-2xl font-bold text-green-600">{{ $stats['fastest'] !== null ? $stats['fastest'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Fastest</div></div>
                    <div><div class="text-2xl font-bold text-red-600">{{ $stats['slowest'] !== null ? $stats['slowest'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Slowest</div></div>
                    <div><div class="text-2xl font-bold">{{ $stats['completed_count'] }}</div><div class="text-xs text-gray-500 mt-1">Completed</div></div>
                    <div><div class="text-2xl font-bold">{{ $stats['open_count'] }}</div><div class="text-xs text-gray-500 mt-1">Still active</div></div>
                    <div><div class="text-2xl font-bold text-amber-600">{{ $stats['avg_open_age'] !== null ? $stats['avg_open_age'].'d' : '—' }}</div><div class="text-xs text-gray-500 mt-1">Avg. age (active)</div></div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Completion time is measured from when a document was received (or encoded) to when it was completed/archived.</p>
            </x-card>

        @elseif($type === 'aging')
            @php $as = $agingStats; $bk = $as['buckets']; @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Active documents" :value="$as['count']" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Oldest document" :value="$as['oldest'] ? $as['oldest']->totalTime() : '—'" color="red"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Avg. time w/ holder" :value="$as['avg_holder'] !== null ? \App\Models\Document::humanDuration($as['avg_holder']) : '—'" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Longest w/ a holder" :value="$as['longest_holder'] !== null ? \App\Models\Document::humanDuration($as['longest_holder']) : '—'" color="red"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon></x-stat-card>
            </div>

            {{-- How long active documents have been sitting with their current holder --}}
            <x-card title="How long with the current holder">
                @php
                    $bkMeta = [
                        'under_1h' => ['Under 1 hour', 'green'],
                        'h1_8'     => ['1–8 hours', 'blue'],
                        'h8_24'    => ['8–24 hours', 'amber'],
                        'd1_3'     => ['1–3 days', 'orange'],
                        'over_3d'  => ['Over 3 days', 'red'],
                    ];
                    $bkColors = ['green'=>'bg-green-500','blue'=>'bg-blue-500','amber'=>'bg-amber-500','orange'=>'bg-orange-500','red'=>'bg-red-500'];
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-center">
                    @foreach($bkMeta as $key => [$label, $color])
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full {{ $bkColors[$color] }}"></span>
                                <span class="text-2xl font-bold {{ $bk[$key] > 0 && $color === 'red' ? 'text-red-600' : '' }}">{{ $bk[$key] }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-3">Counts active documents by how long they've been sitting with whoever currently holds them. Pending documents are excluded.</p>
            </x-card>

            <x-card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/40"><tr>
                            <th class="table-th">#</th><th class="table-th">Code</th><th class="table-th">Title</th><th class="table-th">Total time</th><th class="table-th">Currently with</th><th class="table-th">Time w/ holder</th><th class="table-th">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($aging as $i => $doc)
                                <tr @class(['bg-red-50/50 dark:bg-red-900/10' => $doc->secondsWithCurrentHolder() >= 3*86400])>
                                    <td class="table-td text-gray-400" data-label="#">{{ $i + 1 }}</td>
                                    <td class="table-td font-mono text-xs" data-label="Code">{{ $doc->tracking_code }}</td>
                                    <td class="table-td" data-label="Title">{{ $doc->title }}</td>
                                    <td class="table-td font-medium" data-label="Total time">{{ $doc->totalTime() }}</td>
                                    <td class="table-td" data-label="Currently with">
                                        @if($p = $doc->currentPossessor())
                                            {{ $p->name }}<span class="block text-xs text-gray-400">{{ $p->orgShort() }}</span>
                                        @else
                                            <span class="text-amber-600 dark:text-amber-400">Office pool</span><span class="block text-xs text-gray-400">{{ $doc->openPossession?->department?->code ?? $doc->department?->code ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="table-td font-semibold {{ $doc->secondsWithCurrentHolder() >= 3*86400 ? 'text-red-600' : '' }}" data-label="Time w/ holder">{{ $doc->timeWithCurrentHolder() }}</td>
                                    <td class="table-td" data-label="Status"><x-status-badge :status="$doc->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No open documents — nothing is aging. 🎉</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($aging->isNotEmpty())<div class="px-4 py-3 text-sm text-gray-400 border-t border-gray-100 dark:border-gray-700">Oldest first · pending documents are excluded · {{ $aging->count() }} document(s)</div>@endif
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
            <x-card><h3 class="font-semibold text-sm mb-3">Open documents currently held — per staff member</h3><div class="h-64"><canvas id="rWorkload"></canvas></div></x-card>
            <x-card padding="p-0">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40"><tr><th class="table-th">#</th><th class="table-th">Staff member</th><th class="table-th">Office · Division</th><th class="table-th">Open documents held</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($workload as $i => $row)
                            <tr>
                                <td class="table-td text-gray-400" data-label="#">{{ $i + 1 }}</td>
                                <td class="table-td font-medium" data-label="Staff member">{{ $row->currentHolder?->name ?? '—' }}</td>
                                <td class="table-td text-gray-500 dark:text-gray-400" data-label="Office · Division">{{ $row->currentHolder?->orgShort() ?? '—' }}</td>
                                <td class="table-td font-semibold" data-label="Open documents held">{{ $row->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No open documents are currently held by anyone.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>

        @else
            {{-- Document-list reports: stats + charts + table --}}
            @php
                $listTotal = $documents->count();
                $listOpen = ($statusCounts['draft']??0)+($statusCounts['released']??0)+($statusCounts['received']??0)+($statusCounts['forwarded']??0);
                $listPending = $documents->where('is_pending', true)->count();
                $listActive = max(0, $listOpen - $listPending);
                $listDone = ($statusCounts['completed']??0)+($statusCounts['archived']??0);
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Total documents" :value="$listTotal" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Active (ongoing)" :value="$listActive" color="blue"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Pending (paused)" :value="$listPending" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></x-slot:icon></x-stat-card>
                <x-stat-card label="Completed / Archived" :value="$listDone" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
            </div>
            @if($listTotal)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <x-card><h3 class="font-semibold text-sm mb-3">By Status</h3><div class="h-56"><canvas id="lStatus"></canvas></div></x-card>
                    @if($prio)<x-card><h3 class="font-semibold text-sm mb-3">By Priority</h3><div class="h-56"><canvas id="lPriority"></canvas></div></x-card>@endif
                    @if($type === 'by_division')
                        <x-card><h3 class="font-semibold text-sm mb-3">By Division</h3><div class="h-56"><canvas id="lDiv"></canvas></div></x-card>
                    @else
                        <x-card><h3 class="font-semibold text-sm mb-3">By Document Type</h3><div class="h-56"><canvas id="lType"></canvas></div></x-card>
                    @endif
                </div>
            @endif
            <x-card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/40"><tr>
                            <th class="table-th">Code</th><th class="table-th">Title</th><th class="table-th">Type</th><th class="table-th">Division</th>@if($prio)<th class="table-th">Priority</th>@endif<th class="table-th">Status</th><th class="table-th">Holder</th><th class="table-th">Created</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($documents as $doc)
                                <tr>
                                    <td class="table-td font-mono text-xs" data-label="Code">{{ $doc->tracking_code }}</td>
                                    <td class="table-td" data-label="Title">{{ $doc->title }}</td>
                                    <td class="table-td" data-label="Type">{{ $doc->document_type }}</td>
                                    <td class="table-td" data-label="Division">{{ $doc->division?->code ?? '—' }}</td>
                                    @if($prio)<td class="table-td" data-label="Priority">{{ ucfirst($doc->priority) }}</td>@endif
                                    <td class="table-td" data-label="Status">{{ \App\Models\Document::statusLabel($doc->status) }}</td>
                                    <td class="table-td" data-label="Holder">{{ $doc->currentHolder?->name ?? '—' }}</td>
                                    <td class="table-td text-xs text-gray-400" data-label="Created">{{ $doc->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $prio ? 8 : 7 }}" class="px-4 py-8 text-center text-sm text-gray-400">No documents match this report.</td></tr>
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
                mk('lDiv', @json($divCounts ?? []), 'bar', '#6366f1');
            @endif
        };
        const run = () => requestAnimationFrame(initCharts);
        if (document.readyState === 'complete') run();
        else window.addEventListener('load', run);
        })();
    </script>
    @endpush
</x-app-layout>
