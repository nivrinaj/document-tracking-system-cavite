<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
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
            <x-stat-card label="{{ $isHead ? 'Awaiting Release' : 'My Drafts (to release)' }}" :value="$stats['awaiting_release']" color="amber">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="In Transit (to receive)" :value="$stats['in_transit']" color="blue">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="In Progress (received)" :value="$stats['active']" color="primary">
                <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></x-slot:icon>
            </x-stat-card>
            <x-stat-card label="Completed / Archived" :value="$stats['completed']" color="green">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Action queues --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">📥 Waiting for you to receive</h2>
                        <span class="text-xs text-gray-400">{{ $toReceive->count() }} item(s)</span>
                    </div>
                    @forelse($toReceive as $doc)
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
                    @empty
                        <p class="text-sm text-gray-400 py-4 text-center">Nothing waiting to be received.</p>
                    @endforelse
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">⚡ In your hands (forward or archive)</h2>
                        <span class="text-xs text-gray-400">{{ $toAction->count() }} item(s)</span>
                    </div>
                    @forelse($toAction as $doc)
                        <a href="{{ route('documents.show', $doc) }}" class="flex items-center justify-between gap-3 p-3 -mx-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="min-w-0">
                                <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                                <div class="text-xs text-gray-400">{{ $doc->tracking_code }} · ⏱ {{ $doc->updated_at->diffForHumans(null, true) }} in your hands</div>
                            </div>
                            <x-priority-badge :priority="$doc->priority" />
                        </a>
                    @empty
                        <p class="text-sm text-gray-400 py-4 text-center">No documents to act on.</p>
                    @endforelse
                </x-card>

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
            </div>

            {{-- Sidebar widgets --}}
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

                <x-card>
                    <h2 class="font-semibold mb-3">By priority</h2>
                    @if(array_sum($priorityBreakdown) > 0)
                        <div class="h-48"><canvas id="priorityChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400">No data yet.</p>
                    @endif
                </x-card>

                <x-card>
                    <h2 class="font-semibold mb-3">Recent activity</h2>
                    <ol class="space-y-3">
                        @forelse($activity as $log)
                            <li class="flex gap-3">
                                <div class="mt-1.5 w-2 h-2 rounded-full shrink-0" style="background: var(--color-primary)"></div>
                                <div class="min-w-0">
                                    <p class="text-sm">
                                        <span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span>
                                        {{ strtolower($log->actionLabel()) }}
                                        @if($log->toUser) → {{ $log->toUser->name }} @endif
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ $log->document?->title }} · {{ $log->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </li>
                        @empty
                            <p class="text-sm text-gray-400">No activity yet.</p>
                        @endforelse
                    </ol>
                </x-card>
            </div>
        </div>
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
