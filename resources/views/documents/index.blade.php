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
                      isSuperAdmin: {{ $isSuperAdmin ? 'true' : 'false' }},
                      isDeptHead: {{ $isDeptHead ? 'true' : 'false' }},
                      canFilterStaff: {{ $canFilterStaff ? 'true' : 'false' }},
                      dept: '{{ $isSuperAdmin ? request('department_id') : '' }}', deptOpen: false, deptSearch: '',
                      divId: '{{ request('division_id') }}', divOpen: false, divSearch: '',
                      userId: '{{ request('user_id') }}', userOpen: false, userSearch: '',
                      departments: @js($departments->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name])),
                      divisions: @js($divisions->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name,'department_id'=>$d->department_id])),
                      staff: @js($staffOptions->map(fn($u)=>['id'=>$u->id,'name'=>$u->name,'department_id'=>$u->department_id,'division_id'=>$u->division_id])),
                      get visibleDivs() { return this.divisions.filter(d => !this.dept || String(d.department_id) === String(this.dept)); },
                      get filteredDepts() { const q = this.deptSearch.toLowerCase().trim(); return this.departments.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get filteredDivs() { const q = this.divSearch.toLowerCase().trim(); return this.visibleDivs.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get deptLabel() { const d = this.departments.find(x => String(x.id) === String(this.dept)); return d ? d.name : 'All departments'; },
                      get divLabel() { const d = this.divisions.find(x => String(x.id) === String(this.divId)); return d ? d.name : 'All divisions'; },
                      get scopedStaff() {
                          return this.staff.filter(u =>
                              (!this.isSuperAdmin || !this.dept || String(u.department_id) === String(this.dept)) &&
                              (!this.divId || String(u.division_id) === String(this.divId))
                          );
                      },
                      get filteredStaff() { const q = this.userSearch.toLowerCase().trim(); return this.scopedStaff.filter(u => !q || u.name.toLowerCase().includes(q)); },
                      get userLabel() { const u = this.staff.find(x => String(x.id) === String(this.userId)); return u ? u.name : 'All staff'; },
                  }">
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title / code / ref no…" class="input">
                </div>
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

                {{-- Department — Super Admin only; everyone else is implicitly their own department. --}}
                @if($isSuperAdmin)
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Department</label>
                    <div class="relative" @click.outside="deptOpen = false">
                        <input type="hidden" name="department_id" :value="dept">
                        <button type="button" @click="deptOpen = !deptOpen; deptSearch = ''" class="input-btn text-left pr-14 block">
                            <span class="truncate block" :class="!dept ? 'text-gray-400' : ''" x-text="deptLabel"></span>
                        </button>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button" x-show="dept" x-cloak @click.stop="dept = ''; divId = ''; userId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="deptOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="deptOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="deptSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                            <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                <button type="button" @click="dept = ''; divId = ''; userId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All departments</button>
                                <template x-for="d in filteredDepts" :key="d.id"><button type="button" @click="dept = String(d.id); divId = ''; userId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Division — Super Admin (any office) or Dept/Asst Dept Head (their own office's divisions). --}}
                @if($isSuperAdmin || $isDeptHead)
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Division</label>
                    <div class="relative" @click.outside="divOpen = false">
                        <input type="hidden" name="division_id" :value="divId">
                        <button type="button" @click="(dept || isSuperAdmin) && (divOpen = !divOpen); divSearch = ''"
                                class="input-btn text-left pr-14 block" :class="(!dept && isSuperAdmin) ? 'opacity-50 cursor-not-allowed' : ''" :disabled="!dept && isSuperAdmin">
                            <span class="truncate block" :class="!divId ? 'text-gray-400' : ''" x-text="(!dept && isSuperAdmin) ? 'Select department first' : divLabel"></span>
                        </button>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button" x-show="divId" x-cloak @click.stop="divId = ''; userId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="divOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="divOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="divSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                            <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                <button type="button" @click="divId = ''; userId = ''; divOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All divisions</button>
                                <template x-for="d in filteredDivs" :key="d.id"><button type="button" @click="divId = String(d.id); userId = ''; divOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Staff — Super Admin (unrestricted), Dept/Asst Dept Head (own dept, cascades with division), Division Head (own division). --}}
                @if($canFilterStaff)
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Staff</label>
                    <div class="relative" @click.outside="userOpen = false">
                        <input type="hidden" name="user_id" :value="userId">
                        <button type="button" @click="userOpen = !userOpen; userSearch = ''" class="input-btn text-left pr-14 block">
                            <span class="truncate block" :class="!userId ? 'text-gray-400' : ''" x-text="userLabel"></span>
                        </button>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                            <button type="button" x-show="userId" x-cloak @click.stop="userId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="userOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="userOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="userSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                            <div class="max-h-56 overflow-y-auto py-1 text-sm">
                                <button type="button" @click="userId = ''; userOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All staff</button>
                                <template x-for="u in filteredStaff" :key="u.id"><button type="button" @click="userId = String(u.id); userOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="u.name"></button></template>
                                <p x-show="!filteredStaff.length" class="px-3 py-2 text-gray-400">No staff match.</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Date range (encoded)</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input" aria-label="From">
                        <span class="text-gray-400 text-sm shrink-0">to</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input" aria-label="To">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] text-gray-400 mb-0.5">Rows per page</label>
                    <select name="per_page" class="input" onchange="this.form.submit()">
                        @foreach([12, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($perPage == $n)>{{ $n }}</option>
                        @endforeach
                    </select>
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
                                // Deadline highlighting (configurable hex per office/global) takes
                                // precedence over the generic SLA aging tint when present.
                                $dlHighlight = $showDeadlineColumn ? $doc->deadlineHighlight() : null;
                                $tint = $dlHighlight ? null : match (true) {
                                    $od === 'overdue' => 'red',
                                    $od === 'warning' => 'amber',
                                    default => null,
                                };
                            @endphp
                            <tr @class([
                                'hover:bg-gray-50 dark:hover:bg-gray-700/40' => ! $tint && ! $dlHighlight,
                                'bg-amber-50/70 dark:bg-amber-900/15 hover:bg-amber-50 dark:hover:bg-amber-900/25' => $tint === 'amber',
                            ])
                                @if($dlHighlight) style="background-color: {{ $dlHighlight['color'] }}1a" title="{{ $dlHighlight['label'] }}" @endif>
                                <td class="table-td font-mono text-xs" data-label="Tracking Code">{{ $doc->tracking_code }}</td>
                                <td class="table-td" data-label="Title">
                                    <div class="font-medium flex items-center gap-2">
                                        {{ $doc->title }}
                                        @if($dlHighlight)<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide text-white" style="background-color: {{ $dlHighlight['color'] }}">{{ $dlHighlight['label'] }}</span>
                                        @elseif($od === 'overdue')<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Overdue</span>
                                        @elseif($od === 'warning')<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Due soon</span>@endif
                                        @if($doc->is_transmittal)<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300" title="Transmittal of {{ $doc->transmittal_quantity }} {{ $doc->document_type }}">📄 ×{{ $doc->transmittal_quantity }}</span>@endif
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
                                    @elseif($doc->isAwaitingHeadClaim())
                                        <div class="font-medium">{{ $doc->currentHolder->name }} (Dept Head)</div>
                                        <div class="text-xs text-gray-400">In queue — any staff may claim</div>
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
                                            <div class="font-medium" @if($dlHighlight) style="color: {{ $dlHighlight['color'] }}" @endif>{{ $doc->deadline->format('M j, Y') }}</div>
                                            @if($dlHighlight)
                                                <span class="text-[11px]" style="color: {{ $dlHighlight['color'] }}">{{ $dlHighlight['label'] }}</span>
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
