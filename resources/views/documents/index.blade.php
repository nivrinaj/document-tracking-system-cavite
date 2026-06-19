<x-app-layout>
    <x-slot name="header">Document Tracking</x-slot>

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Documents</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track incoming documents and their movement.</p>
            </div>
            @can('documents.create')
                <x-btn :href="route('documents.create')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Encode Document
                </x-btn>
            @endcan
        </div>

        {{-- Filters --}}
        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title / code / ref no…" class="input">
                <select name="status" class="input">
                    <option value="">All statuses</option>
                    @foreach(['draft','released','received','forwarded','archived','completed'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <select name="priority" class="input">
                    <option value="">All priorities</option>
                    @foreach(['urgent','high','normal','low'] as $p)
                        <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
                <select name="division_id" class="input">
                    <option value="">All divisions</option>
                    @foreach($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id')==$d->id)>{{ $d->code }} — {{ $d->name }}</option>@endforeach
                </select>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input">
                </div>
                <div class="sm:col-span-2 lg:col-span-3 flex gap-2">
                    <x-btn type="submit">Filter</x-btn>
                    <x-btn :href="route('documents.index')" variant="secondary">Reset</x-btn>
                </div>
            </form>
        </x-card>

        {{-- Table --}}
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            <th class="table-th">Tracking Code</th>
                            <th class="table-th">Title</th>
                            <th class="table-th">Priority</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Current Holder</th>
                            <th class="table-th">Updated</th>
                            <th class="table-th text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($documents as $doc)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td font-mono text-xs" data-label="Tracking Code">{{ $doc->tracking_code }}</td>
                                <td class="table-td" data-label="Title">
                                    <div class="font-medium">{{ $doc->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->document_type }} @if($doc->reference_no) · {{ $doc->reference_no }} @endif</div>
                                </td>
                                <td class="table-td" data-label="Priority"><x-priority-badge :priority="$doc->priority" /></td>
                                <td class="table-td" data-label="Status"><x-status-badge :status="$doc->status" /></td>
                                <td class="table-td" data-label="Current Holder">{{ $doc->currentHolder?->name ?? '—' }}</td>
                                <td class="table-td text-xs" data-label="Updated">
                                    <span class="inline-flex items-center gap-1.5 text-gray-400">
                                        @unless($doc->isClosed())
                                            <span class="w-2 h-2 rounded-full
                                                @class([
                                                    'bg-green-500' => $doc->agingColor()==='green',
                                                    'bg-amber-500' => $doc->agingColor()==='amber',
                                                    'bg-red-500' => $doc->agingColor()==='red',
                                                ])" title="Idle time"></span>
                                        @endunless
                                        {{ $doc->updated_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="table-td text-right" data-label="">
                                    <a href="{{ route('documents.show', $doc) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white shadow-sm hover:opacity-90"
                                       style="background: var(--color-primary)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No documents found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $documents->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
