<x-app-layout>
    <x-slot name="header">Backups</x-slot>

    <div class="space-y-5">
        <div class="[grid-template-columns:repeat(auto-fit,minmax(150px,1fr))] grid gap-4">
            <x-stat-card label="Database size" :value="\App\Services\BackupService::formatBytes($usage['db_size'])" color="blue">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 1.657 3.582 3 8 3s8-1.343 8-3V7M4 7c0 1.657 3.582 3 8 3s8-1.343 8-3M4 7c0-1.657 3.582-3 8-3s8 1.343 8 3"/>
            </x-stat-card>
            <x-stat-card label="Attachments size" :value="\App\Services\BackupService::formatBytes($usage['attachments_size'])" color="amber">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </x-stat-card>
            <x-stat-card label="Backups size" :value="\App\Services\BackupService::formatBytes($usage['backups_size'])" color="primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </x-stat-card>
            <x-stat-card label="Server disk free" :value="\App\Services\BackupService::formatBytes($usage['free_bytes'])" :color="$usage['used_percent'] !== null && $usage['used_percent'] >= 90 ? 'red' : ($usage['used_percent'] !== null && $usage['used_percent'] >= 75 ? 'amber' : 'green')">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M7 8h.01M17 16h.01M7 16h.01"/>
            </x-stat-card>
        </div>

        @if($usage['used_percent'] !== null && $usage['used_percent'] >= 90)
            <div class="rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                ⚠ Server disk is {{ $usage['used_percent'] }}% full — free up space soon.
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Each backup bundles a full database dump and every attachment into one downloadable zip. There is no restore button here by design — restoring is a deliberate, manual server action, never a one-click web action.</p>
            <form method="POST" action="{{ route('backups.store') }}" data-confirm="Create a new backup now? This may take a moment.">
                @csrf
                <x-btn type="submit">+ Create Backup</x-btn>
            </form>
        </div>

        <x-card title="Configuration">
            <form method="POST" action="{{ route('backups.config') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="label">mysqldump path <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                    <input type="text" name="mysqldump_path" value="{{ old('mysqldump_path', $mysqldumpOverride) }}"
                           placeholder="Leave blank to use the default: {{ $mysqldumpOverride === '' ? $mysqldumpPath : '' }}"
                           class="input max-w-xl">
                    <p class="text-xs text-gray-400 mt-1">
                        Only needed if <code>mysqldump</code> isn't reachable on the server's PATH.
                        Currently using: <span class="font-mono">{{ $mysqldumpPath }}</span>
                    </p>
                </div>
                <x-btn type="submit" variant="secondary">Save</x-btn>
            </form>
        </x-card>

        <x-card padding="p-0">
            <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/40">
                    <tr><th class="table-th">Filename</th><th class="table-th">Size</th><th class="table-th">Created</th><th class="table-th text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="table-td font-medium" data-label="Filename">{{ $backup['filename'] }}</td>
                            <td class="table-td" data-label="Size">{{ \App\Services\BackupService::formatBytes($backup['size']) }}</td>
                            <td class="table-td" data-label="Created">{{ $backup['modified_at']->format('M j, Y g:i A') }}</td>
                            <td class="table-td text-right whitespace-nowrap" data-label="">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('backups.download', $backup['filename']) }}" class="act-edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                                        Download
                                    </a>
                                    <x-delete-button :action="route('backups.destroy', $backup['filename'])" confirm="Delete backup {{ $backup['filename'] }}? This cannot be undone." />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">No backups yet — click "Create Backup" above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</x-app-layout>
