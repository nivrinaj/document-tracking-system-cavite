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
                  x-data="{
                      isSuperAdmin: {{ auth()->user()->hasRole('Super Admin') ? 'true' : 'false' }},
                      dept: '{{ request('department_id') }}', deptOpen: false, deptSearch: '',
                      divId: '{{ request('division_id') }}', divOpen: false, divSearch: '',
                      departments: @js($departments->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name])),
                      divisions: @js($divisions->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name,'department_id'=>$d->department_id])),
                      get visibleDivs() { return this.divisions.filter(d => !this.dept || String(d.department_id) === String(this.dept)); },
                      get filteredDepts() { const q = this.deptSearch.toLowerCase().trim(); return this.departments.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get filteredDivs() { const q = this.divSearch.toLowerCase().trim(); return this.visibleDivs.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get deptLabel() { const d = this.departments.find(x => String(x.id) === String(this.dept)); return d ? d.name : 'All departments'; },
                      get divLabel() { const d = this.divisions.find(x => String(x.id) === String(this.divId)); return d ? d.name : 'All divisions'; },
                  }">
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title / code / ref no…" class="input">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Status</label>
                    <select name="status" class="input">
                        <option value="">All statuses</option>
                        @foreach(['draft','released','received','forwarded','archived','completed'] as $s)
                            <option value="{{ $s }}" @selected(request('status')===$s)>{{ \App\Models\Document::statusLabel($s) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(\App\Models\Document::priorityEnabled())
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Priority</label>
                    <select name="priority" class="input">
                        <option value="">All priorities</option>
                        @foreach(['urgent','high','normal','low'] as $p)
                            <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Document type</label>
                    <select name="document_type" class="input">
                        <option value="">All document types</option>
                        @foreach($documentTypes as $t)
                            <option value="{{ $t }}" @selected(request('document_type')===$t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Department</label>
                    <div class="relative" @click.outside="deptOpen = false">
                        <input type="hidden" name="department_id" :value="dept">
                        <button type="button" @click="deptOpen = !deptOpen; deptSearch = ''" class="input-btn text-left pr-14 block">
                            <span class="truncate block" :class="!dept ? 'text-gray-400' : ''" x-text="deptLabel"></span>
                        </button>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button" x-show="dept" x-cloak @click.stop="dept = ''; divId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="deptOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="deptOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="deptSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                            <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                <button type="button" @click="dept = ''; divId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All departments</button>
                                <template x-for="d in filteredDepts" :key="d.id"><button type="button" @click="dept = String(d.id); divId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Division</label>
                    <div class="relative" @click.outside="divOpen = false">
                        <input type="hidden" name="division_id" :value="divId">
                        <button type="button" @click="(dept || isSuperAdmin) && (divOpen = !divOpen); divSearch = ''"
                                class="input-btn text-left pr-14 block" :class="(!dept && !isSuperAdmin) ? 'opacity-50 cursor-not-allowed' : ''" :disabled="!dept && !isSuperAdmin">
                            <span class="truncate block" :class="!divId ? 'text-gray-400' : ''" x-text="(!dept && !isSuperAdmin) ? 'Select department first' : divLabel"></span>
                        </button>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button" x-show="divId" x-cloak @click.stop="divId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="divOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="divOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="divSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                            <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                <button type="button" @click="divId = ''; divOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All divisions</button>
                                <template x-for="d in filteredDivs" :key="d.id"><button type="button" @click="divId = String(d.id); divOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Date range (encoded)</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input" aria-label="From">
                        <span class="text-gray-400 text-sm shrink-0">to</span>
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
                            @if($showDeadlineColumn)<th class="table-th">Deadline</th>@endif
                            <th class="table-th">Updated</th>
                            <th class="table-th text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($documents as $doc)
                            @php
                                $od = $doc->overdueState();
                                $dl = $showDeadlineColumn ? $doc->deadlineState() : null;
                                // Deadline highlighting takes precedence over SLA aging when present.
                                $tint = match (true) {
                                    $dl === 'overdue', $dl === 'red' => 'red',
                                    $dl === 'orange' => 'orange',
                                    $od === 'overdue' => 'red',
                                    $od === 'warning' => 'amber',
                                    default => null,
                                };
                            @endphp
                            <tr @class([
                                'hover:bg-gray-50 dark:hover:bg-gray-700/40' => ! $tint,
                                'bg-rose-50/70 dark:bg-rose-900/15 hover:bg-rose-50 dark:hover:bg-rose-900/25' => $tint === 'red',
                                'bg-orange-50/70 dark:bg-orange-900/15 hover:bg-orange-50 dark:hover:bg-orange-900/25' => $tint === 'orange',
                                'bg-amber-50/70 dark:bg-amber-900/15 hover:bg-amber-50 dark:hover:bg-amber-900/25' => $tint === 'amber',
                            ])>
                                <td class="table-td font-mono text-xs" data-label="Tracking Code">{{ $doc->tracking_code }}</td>
                                <td class="table-td" data-label="Title">
                                    <div class="font-medium flex items-center gap-2">
                                        {{ $doc->title }}
                                        @if($od === 'overdue')<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Overdue</span>
                                        @elseif($od === 'warning')<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Due soon</span>@endif
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $doc->document_type }}</div>
                                </td>
                                @if(\App\Models\Document::priorityEnabled())<td class="table-td" data-label="Priority"><x-priority-badge :priority="$doc->priority" /></td>@endif
                                <td class="table-td" data-label="Status"><x-status-badge :status="$doc->status" /></td>
                                <td class="table-td" data-label="Origin (from)">
                                    <div>{{ $doc->creator?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $doc->creator?->orgShort() }}</div>
                                </td>
                                <td class="table-td" data-label="Current Holder">
                                    @if($doc->is_broadcast || $doc->distribution_summary)
                                        <span class="text-gray-500 dark:text-gray-300">📣 Distributed</span>
                                        @if($doc->distribution_summary)<div class="text-xs text-gray-400">{{ $doc->distribution_summary }}</div>@endif
                                    @elseif($doc->current_holder_id && $doc->status === 'draft')
                                        <span class="text-amber-600 dark:text-amber-400">Pending release</span>
                                        <div class="text-xs text-gray-400">to {{ $doc->currentHolder->name }}</div>
                                    @elseif($doc->current_holder_id && in_array($doc->status, ['released','forwarded']))
                                        <span class="text-amber-600 dark:text-amber-400">Awaiting Receipt</span>
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
                                @if($showDeadlineColumn)
                                    <td class="table-td" data-label="Deadline">
                                        @if($doc->deadline)
                                            <div @class([
                                                'font-medium',
                                                'text-rose-600 dark:text-rose-400' => $dl === 'overdue' || $dl === 'red',
                                                'text-orange-600 dark:text-orange-400' => $dl === 'orange',
                                            ])>{{ $doc->deadline->format('M j, Y') }}</div>
                                            @if($dl === 'overdue')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Overdue</span>
                                            @elseif($dl === 'red')
                                                <span class="text-[11px] text-rose-500">≤ 8 working hrs left</span>
                                            @elseif($dl === 'orange')
                                                <span class="text-[11px] text-orange-500">≤ 16 working hrs left</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                @endif
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
                            <tr><td colspan="{{ 7 + (\App\Models\Document::priorityEnabled() ? 1 : 0) + ($showDeadlineColumn ? 1 : 0) }}" class="px-4 py-10 text-center text-sm text-gray-400">No documents found.</td></tr>
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
