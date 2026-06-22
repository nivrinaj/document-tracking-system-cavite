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
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                  x-data="{ dept: '{{ request('department_id') }}', divId: '{{ request('division_id') }}', divisions: @js($divisions) }">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title / code / ref no…" class="input">
                <select name="status" class="input">
                    <option value="">All statuses</option>
                    @foreach(['draft','released','received','forwarded','archived','completed'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ \App\Models\Document::statusLabel($s) }}</option>
                    @endforeach
                </select>
                @if(\App\Models\Document::priorityEnabled())
                <select name="priority" class="input">
                    <option value="">All priorities</option>
                    @foreach(['urgent','high','normal','low'] as $p)
                        <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
                @endif
                <select name="department_id" x-model="dept" @change="divId=''" class="input">
                    <option value="">All departments</option>
                    @foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}</option>@endforeach
                </select>
                <select name="division_id" x-model="divId" class="input">
                    <option value="">All divisions</option>
                    <template x-for="d in divisions.filter(x => !dept || String(x.department_id) === String(dept))" :key="d.id">
                        <option :value="d.id" x-text="d.code + ' — ' + d.name"></option>
                    </template>
                </select>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Date range (encoded)</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input" aria-label="From">
                        <span class="text-gray-400 text-sm">to</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input" aria-label="To">
                    </div>
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
                            @if(\App\Models\Document::priorityEnabled())<th class="table-th">Priority</th>@endif
                            <th class="table-th">Status</th>
                            <th class="table-th">Origin (from)</th>
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
                                @if(\App\Models\Document::priorityEnabled())<td class="table-td" data-label="Priority"><x-priority-badge :priority="$doc->priority" /></td>@endif
                                <td class="table-td" data-label="Status"><x-status-badge :status="$doc->status" /></td>
                                <td class="table-td" data-label="Origin (from)">
                                    <div>{{ $doc->creator?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->creator?->orgShort() }}</div>
                                </td>
                                <td class="table-td" data-label="Current Holder">
                                    @if($doc->is_broadcast)
                                        <span class="text-gray-400">📣 Broadcast</span>
                                    @elseif($doc->current_holder_id && $doc->status === 'draft')
                                        <span class="text-amber-600 dark:text-amber-400">Pending release</span>
                                        <div class="text-xs text-gray-400">to {{ $doc->currentHolder->name }}</div>
                                    @elseif($doc->current_holder_id && in_array($doc->status, ['released','forwarded']))
                                        <span class="text-amber-600 dark:text-amber-400">In transit</span>
                                        <div class="text-xs text-gray-400">to {{ $doc->currentHolder->name }} · awaiting receipt</div>
                                    @elseif($doc->currentHolder)
                                        <div class="font-medium">{{ $doc->currentHolder->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $doc->currentHolder->orgShort() }}</div>
                                        @if($doc->is_pending)<div class="text-xs text-amber-600 dark:text-amber-400">⏸ Pending</div>@endif
                                    @elseif($doc->status === 'released')
                                        <span class="text-amber-600 dark:text-amber-400">📥 To claim</span>
                                        <div class="text-xs text-gray-400">{{ $doc->department?->code }}</div>
                                    @else
                                        <span class="text-gray-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="table-td" data-label="Updated">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0
                                            @class([
                                                'bg-green-500' => !$doc->isClosed() && $doc->agingColor()==='green',
                                                'bg-amber-500' => !$doc->isClosed() && $doc->agingColor()==='amber',
                                                'bg-red-500' => !$doc->isClosed() && $doc->agingColor()==='red',
                                                'bg-gray-300 dark:bg-gray-600' => $doc->isClosed(),
                                            ])" title="{{ $doc->isClosed() ? 'Closed' : 'Idle time' }}"></span>
                                        <span class="leading-tight">
                                            <span class="block text-xs text-gray-600 dark:text-gray-300">{{ $doc->updated_at->diffForHumans() }}</span>
                                            <span class="block text-[11px] text-gray-400">{{ $doc->updated_at->format('M d, g:i A') }}</span>
                                        </span>
                                    </div>
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
                            <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">No documents found.</td></tr>
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
