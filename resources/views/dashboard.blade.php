<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        @if(!empty($settings['announcement']))
            <div x-data="{ show: true }" x-show="show"
                 class="flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 px-4 py-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                <div class="flex-1 text-sm">{{ $settings['announcement'] }}</div>
                <button @click="show = false" class="text-amber-600 hover:text-amber-800">&times;</button>
            </div>
        @endif

        {{-- Greeting + quick actions --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Welcome back, {{ auth()->user()->name }} 👋</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ auth()->user()->division?->name ?? 'No division' }} ·
                    {{ auth()->user()->getRoleNames()->join(', ') }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->canEncode())
                    <x-btn :href="route('documents.create')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Encode
                    </x-btn>
                @endif
                @if(\App\Models\Document::batchReceiveEnabled())
                @can('documents.receive')
                    <x-btn :href="route('documents.batchReceive')" variant="secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        Batch receive
                    </x-btn>
                @endcan
                @endif
                <x-btn :href="route('documents.index')" variant="secondary">All documents</x-btn>
            </div>
        </div>

        {{-- Stat cards — one per workflow stage --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Awaiting Release" :value="$stats['awaiting_release']" color="amber" :href="route('documents.index', ['stage' => 'awaiting_release'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="Awaiting Receipt" :value="$stats['in_transit']" color="blue" :href="route('documents.index', ['stage' => 'in_transit'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="In Progress (received)" :value="$stats['active']" color="primary" :href="route('documents.index', ['stage' => 'in_progress'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="Completed / Archived" :value="$stats['completed']" color="green" :href="route('documents.index', ['stage' => 'completed'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon>
            </x-stat-card>
        </div>

        {{-- Document volume — today / this week / this month, with transmittal
             detail folded in next to a real total instead of floating alone. --}}
        <x-card title="Document Volume" padding="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
                @foreach($volumeSummary as $period)
                    <div class="px-0 sm:px-5 py-3 first:pl-0 first:pt-0 sm:first:pt-3">
                        <div class="text-[11px] uppercase tracking-wider text-gray-400 mb-1">{{ $period['label'] }}</div>
                        <div class="text-2xl font-bold leading-none">{{ $period['total'] }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ \Illuminate\Support\Str::plural('document', $period['total']) }} encoded</div>
                        @if($period['transmittal_count'] > 0)
                            <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1.5 flex items-center gap-1">
                                <span>📄</span> Incl. {{ $period['transmittal_count'] }} {{ \Illuminate\Support\Str::plural('transmittal', $period['transmittal_count']) }} → {{ $period['transmittal_quantity'] }} {{ \Illuminate\Support\Str::plural('document', $period['transmittal_quantity']) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- ════════ Needs your action (front and center) ════════ --}}
        @php
            $nothingPending = $toReceive->isEmpty() && $toAction->isEmpty() && $toRelease->isEmpty() && $toClaim->isEmpty() && $toAcknowledge->isEmpty();
            $pendingTotal = $toReceive->count() + $toAction->count() + $toRelease->count() + $toClaim->count() + $toAcknowledge->count();
        @endphp
        <div>
            <div class="flex items-center gap-2 mb-3">
                <h2 class="font-semibold text-lg">Needs your action</h2>
                @unless($nothingPending)<span class="text-xs px-2 py-0.5 rounded-full bg-[color:var(--color-primary)]/10 dark:bg-[color:var(--color-primary)]/25 text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)] font-medium">{{ $pendingTotal }}</span>@endunless
            </div>

            @if($nothingPending)
                <x-card padding="p-10">
                    <div class="text-center">
                        <div class="mx-auto w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h2 class="font-semibold">You're all caught up 🎉</h2>
                        <p class="text-sm text-gray-400 mt-1">No documents are waiting for your action right now.</p>
                        @if(auth()->user()->canEncode())
                            <div class="mt-4"><x-btn :href="route('documents.create')">Encode a document</x-btn></div>
                        @endif
                    </div>
                </x-card>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                    @php
                        $queues = [
                            ['items' => $toClaim,       'title' => '📥 Transferred to your office — to claim', 'badge' => ['Claim', 'amber']],
                            ['items' => $toAcknowledge, 'title' => '🔔 Waiting for your acknowledgement',       'badge' => ['Acknowledge', 'blue']],
                            ['items' => $toReceive,     'title' => '📥 Waiting for you to receive',             'badge' => ['Receive', 'blue']],
                            ['items' => $toAction,      'title' => '⚡ In your hands (forward or archive)',      'badge' => ['Act', 'indigo']],
                            ['items' => $toRelease,     'title' => '🚀 Drafts ready to release',                'badge' => ['Release', 'amber']],
                        ];
                    @endphp
                    @foreach($queues as $queue)
                        @continue($queue['items']->isEmpty())
                        <x-card>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm">{{ $queue['title'] }}</h3>
                                <span class="text-xs text-gray-400">{{ $queue['items']->count() }}</span>
                            </div>
                            <div class="-mx-1 divide-y divide-gray-50 dark:divide-gray-700/50">
                                @foreach($queue['items'] as $doc)
                                    <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 px-1 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <div class="min-w-0">
                                            <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                            <div class="text-xs text-gray-400 truncate">{{ $doc->tracking_code }} · ⏱ {{ $doc->updated_at->diffForHumans(null, true) }}</div>
                                        </div>
                                        <x-badge :color="$queue['badge'][1]">{{ $queue['badge'][0] }}</x-badge>
                                    </a>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ════════ Insights (charts) ════════ --}}
        <div>
            <h2 class="font-semibold text-lg mb-3">Insights</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-card class="lg:col-span-2">
                    <h3 class="font-semibold text-sm mb-3">Incoming documents — last 14 days</h3>
                    <div class="h-56"><canvas id="trendChart"></canvas></div>
                </x-card>
                <x-card>
                    <h3 class="font-semibold text-sm mb-3">By status</h3>
                    <div class="h-56"><canvas id="statusChart"></canvas></div>
                </x-card>
            </div>
            @if(\App\Models\Document::priorityEnabled())
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                    <x-card>
                        <h3 class="font-semibold text-sm mb-3">By priority</h3>
                        @if(array_sum($priorityBreakdown) > 0)
                            <div class="h-48"><canvas id="priorityChart"></canvas></div>
                            <p class="text-[11px] text-gray-400 text-center mt-2">Tip: click a slice to filter documents.</p>
                        @else
                            <p class="text-sm text-gray-400">No data yet.</p>
                        @endif
                    </x-card>
                </div>
            @endif
        </div>

        {{-- Recent activity (full-width rows, capped height) --}}
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold">Recent activity</h2>
                @can('logs.view')
                    <a href="{{ route('logs.index') }}" class="text-xs link">View all logs →</a>
                @endcan
            </div>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                @forelse($activity as $log)
                    <li class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0" style="background: var(--color-primary)"></span>
                            <div class="min-w-0">
                                <p class="text-sm">
                                    <span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span>
                                    {{ strtolower($log->actionLabel()) }}
                                    @if($log->toUser) <span class="text-gray-400">→</span> {{ $log->toUser->name }} @endif
                                </p>
                                @if($log->document)
                                    <p class="text-xs text-gray-400 truncate">{{ $log->document->title }} · <span class="font-mono">{{ $log->document->tracking_code }}</span></p>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No activity yet.</li>
                @endforelse
            </ol>
        </x-card>
    </div>

    @push('scripts')
    <script>
        (function () {
        const initCharts = () => {
            const css = getComputedStyle(document.documentElement);
            const primary = css.getPropertyValue('--color-primary').trim() || '#4f46e5';
            const dark = document.documentElement.classList.contains('dark');
            const grid = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
            const tick = dark ? '#9ca3af' : '#6b7280';
            const palette = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#ef4444','#a855f7','#14b8a6','#64748b'];
            Chart.defaults.color = tick;
            Chart.defaults.font.family = "Figtree, system-ui, sans-serif";
            const docsUrl = '{{ route('documents.index') }}';

            // Incoming trend (line)
            const trend = @json($trend);
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [{
                        label: 'Documents', data: trend.map(t => t.count),
                        borderColor: primary, backgroundColor: primary + '33',
                        fill: true, tension: 0.35, pointRadius: 3, borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Status doughnut
            const status = @json($statusBreakdown);
            if (Object.keys(status).length) {
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(status).map(s => s[0].toUpperCase() + s.slice(1)),
                        datasets: [{ data: Object.values(status), backgroundColor: palette, borderWidth: 0 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                        onClick: (evt, els, chart) => { if (els.length) { window.location = docsUrl + '?status=' + chart.data.labels[els[0].index].toLowerCase(); } },
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
                });
            }

            // Priority doughnut
            const priorityEl = document.getElementById('priorityChart');
            const priority = @json($priorityBreakdown);
            if (priorityEl && Object.keys(priority).length) {
                const pcolors = { urgent: '#ef4444', high: '#f59e0b', normal: '#0ea5e9', low: '#94a3b8' };
                new Chart(priorityEl, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(priority).map(s => s[0].toUpperCase() + s.slice(1)),
                        datasets: [{ data: Object.values(priority), backgroundColor: Object.keys(priority).map(k => pcolors[k] || '#94a3b8'), borderWidth: 0 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '60%',
                        onClick: (evt, els, chart) => { if (els.length) { window.location = docsUrl + '?priority=' + chart.data.labels[els[0].index].toLowerCase(); } },
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
                });
            }
        };
        const run = () => requestAnimationFrame(initCharts);
        if (document.readyState === 'complete') run();
        else window.addEventListener('load', run);
        })();
    </script>
    @endpush
</x-app-layout>
