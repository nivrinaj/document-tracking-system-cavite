<x-app-layout>
    <x-slot name="header">Logs &amp; History</x-slot>

    <div class="space-y-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            @if($canViewAll)
                Complete audit trail — logins, logouts and every action across the system.
            @else
                Your activity history — your logins and the actions you have taken.
            @endif
        </p>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search details…" class="input">
                <select name="action" class="input">
                    <option value="">All activity</option>
                    @foreach($actions as $val => $label)<option value="{{ $val }}" @selected(request('action')===$val)>{{ $label }}</option>@endforeach
                </select>
                @if($canViewAll)
                    <select name="actor_id" class="input">
                        <option value="">All users</option>
                        @foreach($users as $u)<option value="{{ $u->id }}" @selected(request('actor_id')==$u->id)>{{ $u->name }}</option>@endforeach
                    </select>
                @endif
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Date range</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input" aria-label="From">
                        <span class="text-gray-400 text-sm">to</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input" aria-label="To">
                    </div>
                </div>
                <div class="sm:col-span-2 lg:col-span-{{ $canViewAll ? '4' : '3' }} flex gap-2"><x-btn type="submit">Filter</x-btn><x-btn :href="route('logs.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            <th class="table-th">When</th>
                            @if($canViewAll)<th class="table-th">User</th>@endif
                            <th class="table-th">Action</th>
                            <th class="table-th">Details</th>
                            <th class="table-th">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td text-xs text-gray-400 whitespace-nowrap" data-label="When">
                                    {{ $log->created_at->format('M d, Y g:i A') }}
                                    <div class="text-[11px]">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                @if($canViewAll)
                                    <td class="table-td" data-label="User">
                                        <div>{{ $log->user?->name ?? 'Guest' }}</div>
                                        @if($log->user)<div class="text-xs text-gray-400">{{ $log->user->orgShort() }}</div>@endif
                                    </td>
                                @endif
                                <td class="table-td" data-label="Action"><x-badge :color="$log->actionColor()">{{ $log->actionLabel() }}</x-badge></td>
                                <td class="table-td" data-label="Details">{{ $log->description }}</td>
                                <td class="table-td text-xs text-gray-400" data-label="IP">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canViewAll ? 5 : 4 }}" class="px-4 py-10 text-center text-sm text-gray-400">No activity recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $logs->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
