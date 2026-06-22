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

        {{-- Greeting --}}
        <div>
            <h1 class="text-xl font-semibold">Welcome back, {{ auth()->user()->name }} 👋</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ auth()->user()->division?->name ?? 'No division' }} ·
                {{ auth()->user()->getRoleNames()->join(', ') }}
            </p>
        </div>

        {{-- Stat cards — one per workflow stage --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Awaiting Release" :value="$stats['awaiting_release']" color="amber" :href="route('documents.index', ['stage' => 'awaiting_release'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="In Transit (to receive)" :value="$stats['in_transit']" color="blue" :href="route('documents.index', ['stage' => 'in_transit'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="In Progress (received)" :value="$stats['active']" color="primary" :href="route('documents.index', ['stage' => 'in_progress'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="Completed / Archived" :value="$stats['completed']" color="green" :href="route('documents.index', ['stage' => 'completed'])">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon>
            </x-stat-card>
        </div>

        {{-- Charts row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-card class="lg:col-span-2">
                <h2 class="font-semibold mb-3">Incoming documents — last 14 days</h2>
                <div class="h-56"><canvas id="trendChart"></canvas></div>
            </x-card>
            <x-card>
                <h2 class="font-semibold mb-3">By status</h2>
                <div class="h-56"><canvas id="statusChart"></canvas></div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Action queues (left) --}}
            <div class="lg:col-span-2 space-y-6">
                @php $nothingPending = $toReceive->isEmpty() && $toAction->isEmpty() && $toRelease->isEmpty() && $toClaim->isEmpty(); @endphp

                @if($nothingPending)
                    <x-card padding="p-10">
                        <div class="text-center">
                            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h2 class="font-semibold">You're all caught up 🎉</h2>
                            <p class="text-sm text-gray-400 mt-1">No documents are waiting for your action right now.</p>
                            @can('documents.create')
                                <div class="mt-4"><x-btn :href="route('documents.create')">Encode a document</x-btn></div>
                            @endcan
                        </div>
                    </x-card>
                @else
                    @if($toClaim->isNotEmpty())
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">📥 Transferred to your office — to claim</h2>
                            <span class="text-xs text-gray-400">{{ $toClaim->count() }} item(s)</span>
                        </div>
                        @foreach($toClaim as $doc)
                            <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 p-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->tracking_code }} · from {{ $doc->creator?->name }} · ⏱ {{ $doc->updated_at->diffForHumans(null, true) }} waiting</div>
                                </div>
                                <x-badge color="amber">Claim</x-badge>
                            </a>
                        @endforeach
                    </x-card>
                    @endif

                    @if($toReceive->isNotEmpty())
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">📥 Waiting for you to receive</h2>
                            <span class="text-xs text-gray-400">{{ $toReceive->count() }} item(s)</span>
                        </div>
                        @foreach($toReceive as $doc)
                            <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 p-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->tracking_code }} · from {{ $doc->creator?->name }} · ⏱ waiting {{ $doc->updated_at->diffForHumans(null, true) }}</div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-priority-badge :priority="$doc->priority" />
                                    <x-status-badge :status="$doc->status" />
                                </div>
                            </a>
                        @endforeach
                    </x-card>
                    @endif

                    @if($toAction->isNotEmpty())
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">⚡ In your hands (forward or archive)</h2>
                            <span class="text-xs text-gray-400">{{ $toAction->count() }} item(s)</span>
                        </div>
                        @foreach($toAction as $doc)
                            <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 p-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->tracking_code }} · ⏱ {{ $doc->updated_at->diffForHumans(null, true) }} in your hands</div>
                                </div>
                                <x-priority-badge :priority="$doc->priority" />
                            </a>
                        @endforeach
                    </x-card>
                    @endif

                    @if($toRelease->isNotEmpty())
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">🚀 Drafts ready to release</h2>
                            <span class="text-xs text-gray-400">{{ $toRelease->count() }} item(s)</span>
                        </div>
                        @foreach($toRelease as $doc)
                            <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 p-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->tracking_code }} · assigned to {{ $doc->currentHolder?->name }}</div>
                                </div>
                                <x-badge color="amber">Draft</x-badge>
                            </a>
                        @endforeach
                    </x-card>
                    @endif
                @endif
            </div>

            {{-- Right widgets --}}
            <div class="space-y-6">
                @can('documents.create')
                <x-card>
                    <h2 class="font-semibold mb-3">Quick actions</h2>
                    <div class="space-y-2">
                        <x-btn :href="route('documents.create')" class="w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Encode new document
                        </x-btn>
                        <x-btn :href="route('documents.index')" variant="secondary" class="w-full">View all documents</x-btn>
                    </div>
                </x-card>
                @endcan

                @if(\App\Models\Document::priorityEnabled())
                <x-card>
                    <h2 class="font-semibold mb-3">By priority</h2>
                    @if(array_sum($priorityBreakdown) > 0)
                        <div class="h-48"><canvas id="priorityChart"></canvas></div>
                        <p class="text-[11px] text-gray-400 text-center mt-2">Tip: click a slice to filter documents.</p>
                    @else
                        <p class="text-sm text-gray-400">No data yet.</p>
                    @endif
                </x-card>
                @endif
            </div>
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
