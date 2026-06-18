<x-app-layout>
    <x-slot name="header">Logs &amp; History</x-slot>

    <div class="space-y-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">Complete audit trail of every action across all documents.</p>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Document title / code…" class="input">
                <select name="action" class="input">
                    <option value="">All actions</option>
                    @foreach($actions as $a)<option value="{{ $a }}" @selected(request('action')===$a)>{{ ucfirst($a) }}</option>@endforeach
                </select>
                <select name="actor_id" class="input">
                    <option value="">All users</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected(request('actor_id')==$u->id)>{{ $u->name }}</option>@endforeach
                </select>
                <div class="flex gap-2"><x-btn type="submit" class="flex-1">Filter</x-btn><x-btn :href="route('logs.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr><th class="table-th">When</th><th class="table-th">Action</th><th class="table-th">Document</th><th class="table-th">By</th><th class="table-th">To</th><th class="table-th">Remarks</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td text-xs text-gray-400 whitespace-nowrap" data-label="When">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                <td class="table-td" data-label="Action"><x-badge :color="$log->actionColor()">{{ $log->actionLabel() }}</x-badge></td>
                                <td class="table-td" data-label="Document">
                                    @if($log->document)
                                        <a href="{{ route('documents.show', $log->document) }}" class="link">{{ $log->document->title }}</a>
                                        <div class="text-xs text-gray-400 font-mono">{{ $log->document->tracking_code }}</div>
                                    @else <span class="text-gray-400">(deleted)</span> @endif
                                </td>
                                <td class="table-td" data-label="By">{{ $log->actor?->name ?? '—' }}</td>
                                <td class="table-td" data-label="To">{{ $log->toUser?->name ?? '—' }}</td>
                                <td class="table-td text-gray-500 dark:text-gray-400 sm:max-w-xs sm:truncate" data-label="Remarks">{{ $log->remarks }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">No log entries.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $logs->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
