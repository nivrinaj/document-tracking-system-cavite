<x-app-layout>
    <x-slot name="header">Report Result</x-slot>

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
                <x-btn :href="route('reports.generate', array_merge(request()->query(), ['format' => 'pdf']))">Download PDF</x-btn>
            </div>
        </div>

        @if($type === 'summary')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <x-card><h3 class="font-semibold text-sm mb-3">Status</h3><div class="h-56"><canvas id="rStatus"></canvas></div></x-card>
                <x-card><h3 class="font-semibold text-sm mb-3">Priority</h3><div class="h-56"><canvas id="rPriority"></canvas></div></x-card>
                <x-card><h3 class="font-semibold text-sm mb-3">Division</h3><div class="h-56"><canvas id="rDivision"></canvas></div></x-card>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-card title="By Status">
                    @foreach($byStatus as $k => $v)
                        <div class="flex justify-between text-sm py-1"><span class="capitalize">{{ $k }}</span><span class="font-medium">{{ $v }}</span></div>
                    @endforeach
                </x-card>
                <x-card title="By Priority">
                    @foreach($byPriority as $k => $v)
                        <div class="flex justify-between text-sm py-1"><span class="capitalize">{{ $k }}</span><span class="font-medium">{{ $v }}</span></div>
                    @endforeach
                </x-card>
                <x-card title="By Division">
                    @foreach($byDivision as $k => $v)
                        <div class="flex justify-between text-sm py-1"><span>{{ $k }}</span><span class="font-medium">{{ $v }}</span></div>
                    @endforeach
                </x-card>
            </div>
        @elseif($type === 'staff_workload')
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
                                    <td class="table-td" data-label="Status">{{ ucfirst($doc->status) }}</td>
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

    @if($type === 'summary')
    @push('scripts')
    <script>
        (function () {
        const initCharts = () => {
            const dark = document.documentElement.classList.contains('dark');
            Chart.defaults.color = dark ? '#9ca3af' : '#6b7280';
            const palette = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#ef4444','#a855f7','#14b8a6','#64748b'];
            const mk = (id, obj, type='doughnut', colors=palette) => {
                const el = document.getElementById(id);
                if (!el || !Object.keys(obj).length) return;
                new Chart(el, {
                    type,
                    data: { labels: Object.keys(obj).map(s => s[0].toUpperCase()+s.slice(1)),
                        datasets: [{ data: Object.values(obj), backgroundColor: colors, borderWidth: type==='bar'?0:0 }] },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: type!=='bar', position: 'bottom', labels:{boxWidth:12,padding:10} } },
                        scales: type==='bar' ? { y: { beginAtZero:true, ticks:{precision:0} } } : {} }
                });
            };
            mk('rStatus', @json($byStatus));
            mk('rPriority', @json($byPriority), 'doughnut', ['#ef4444','#f59e0b','#0ea5e9','#94a3b8','#6366f1']);
            mk('rDivision', @json($byDivision), 'bar', '#6366f1');
        };
        const run = () => requestAnimationFrame(initCharts);
        if (document.readyState === 'complete') run();
        else window.addEventListener('load', run);
        })();
    </script>
    @endpush
    @endif
</x-app-layout>
