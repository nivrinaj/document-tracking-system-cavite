<x-app-layout>
    <x-slot name="header">Email Log</x-slot>

    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">A history of every email the system has attempted to send, whether it succeeded or failed.</p>
            <a href="{{ route('notification-settings.edit') }}" class="text-sm link">&larr; Back to Notification Settings</a>
        </div>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search recipient email…" class="input">
                <select name="type" class="input">
                    <option value="">All types</option>
                    <option value="test" @selected(request('type')==='test')>Test email</option>
                    @foreach($types as $key => $meta)
                        <option value="{{ $key }}" @selected(request('type')===$key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <select name="status" class="input">
                    <option value="">Any status</option>
                    <option value="sent" @selected(request('status')==='sent')>Sent</option>
                    <option value="failed" @selected(request('status')==='failed')>Failed</option>
                </select>
                <select name="per_page" class="input" onchange="this.form.submit()">
                    @foreach([12, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage == $n)>{{ $n }} rows</option>
                    @endforeach
                </select>
                <div class="sm:col-span-2 lg:col-span-4 flex gap-2"><x-btn type="submit">Filter</x-btn><x-btn :href="route('email-logs.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr><th class="table-th">Recipient</th><th class="table-th">Subject</th><th class="table-th">Type</th><th class="table-th">Status</th><th class="table-th">Sent</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td font-medium" data-label="Recipient">{{ $log->recipient }}</td>
                                <td class="table-td" data-label="Subject">{{ $log->subject }}</td>
                                <td class="table-td" data-label="Type">{{ $types[$log->type]['label'] ?? ($log->type === 'test' ? 'Test email' : ($log->type ?: '—')) }}</td>
                                <td class="table-td" data-label="Status">
                                    @if($log->status === 'sent')
                                        <x-badge color="green">Sent</x-badge>
                                    @else
                                        <x-badge color="red" title="{{ $log->error }}">Failed</x-badge>
                                    @endif
                                </td>
                                <td class="table-td whitespace-nowrap" data-label="Sent">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                            @if($log->status === 'failed' && $log->error)
                                <tr class="bg-red-50/50 dark:bg-red-900/10">
                                    <td colspan="5" class="px-4 py-2 text-xs text-red-600 dark:text-red-400 font-mono">{{ $log->error }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No emails sent yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $logs->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
