<x-app-layout>
    <x-slot name="header">Document Details</x-slot>

    <div x-data="{ panel: null }" class="space-y-5">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-semibold">{{ $document->title }}</h1>
                    <x-status-badge :status="$document->status" />
                    <x-priority-badge :priority="$document->priority" />
                </div>
                <p class="text-sm text-gray-400 font-mono mt-1">{{ $document->tracking_code }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('documents.index') }}" class="text-sm link">← Back to list</a>
                @can('update', $document)
                    <x-btn :href="route('documents.edit', $document)" variant="secondary">Edit</x-btn>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: details + timeline --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Information">
                    {{-- Routing highlight: where it came from → where it is now --}}
                    <div class="grid sm:grid-cols-[1fr_auto_1fr] items-stretch gap-3 mb-6">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-[11px] uppercase tracking-wider text-gray-400 mb-1">From · origin</div>
                            <div class="font-semibold">{{ $document->creator?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $document->creator?->orgUnit() }}</div>
                        </div>
                        <div class="hidden sm:flex items-center justify-center text-gray-300 dark:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                        <div class="rounded-xl border-2 p-4" style="border-color: var(--color-primary)">
                            <div class="text-[11px] uppercase tracking-wider text-gray-400 mb-1">Currently with</div>
                            @php $h = $document->currentHolder; @endphp
                            @if($document->is_broadcast)
                                <div class="font-semibold">📣 Broadcast memo</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Sent to multiple recipients</div>
                            @elseif($h && $document->status === 'draft')
                                {{-- Assigned but not released — the encoder still physically holds it. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">Pending release</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Assigned to {{ $h->name }} ({{ $h->orgShort() }}) — not yet handed over</div>
                            @elseif($h && in_array($document->status, ['released', 'forwarded']))
                                {{-- Handed over / sent, but the recipient hasn't confirmed receipt yet. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">In transit</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $document->status === 'forwarded' ? 'Forwarded' : 'Released' }} to {{ $h->name }} ({{ $h->orgShort() }}) — awaiting receipt</div>
                            @elseif($h)
                                {{-- Received (or closed): the holder actually possesses it. --}}
                                <div class="font-semibold">{{ $h->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $h->orgUnit() }}</div>
                            @elseif($document->status === 'released')
                                {{-- Transferred to an office pool, not yet claimed. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">Awaiting claim</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Transferred to {{ $document->department?->code }} — not yet claimed</div>
                            @else
                                <div class="font-semibold text-gray-500 dark:text-gray-400">Not yet assigned</div>
                                <div class="text-xs text-gray-400">⏳ In transit — awaiting routing</div>
                            @endif
                        </div>
                    </div>

                    {{-- Last status action (most recent movement) --}}
                    @php $last = $document->logs->first(); @endphp
                    @if($last)
                        <div class="flex items-center gap-x-2 gap-y-1 flex-wrap mb-6 -mt-2 text-sm">
                            <span class="text-[11px] uppercase tracking-wider text-gray-400">Last action</span>
                            <x-badge :color="$last->actionColor()">{{ $last->actionLabel() }}</x-badge>
                            <span class="text-gray-600 dark:text-gray-300">by {{ $last->actor?->name ?? 'System' }}</span>
                            <span class="text-gray-400">· {{ $last->created_at->format('M d, Y g:i A') }} ({{ $last->created_at->diffForHumans() }})</span>
                        </div>
                    @endif

                    {{-- Document facts --}}
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Type</dt><dd class="mt-0.5">{{ $document->document_type }}</dd></div>
                        @if($document->voucher_number)
                            <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Voucher No.</dt><dd class="mt-0.5 font-mono">{{ $document->voucher_number }}</dd></div>
                        @endif
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Reference No.</dt><dd class="mt-0.5">{{ $document->reference_no ?? '—' }}</dd></div>
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Source / Origin</dt><dd class="mt-0.5">{{ $document->source ?? '—' }}</dd></div>
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Current location</dt><dd class="mt-0.5">{{ $document->department?->code ?? '—' }}@if($document->division) · {{ $document->division->name }}@endif</dd></div>
                    </dl>

                    {{-- Timeline facts --}}
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 text-sm mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Received</dt><dd class="mt-0.5">{{ $document->received_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Released</dt><dd class="mt-0.5">{{ $document->released_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                        <div><dt class="text-[11px] uppercase tracking-wider text-gray-400">Age</dt><dd class="mt-0.5">{{ $document->age() }}</dd></div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-gray-400">{{ $document->isClosed() ? 'Turnaround' : 'Idle time' }}</dt>
                            <dd class="mt-0.5">
                                @if($document->isClosed())
                                    {{ $document->turnaround() ?? '—' }}
                                @else
                                    <x-badge :color="$document->agingColor()">{{ $document->elapsedSinceLastAction() }}</x-badge>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($document->description)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="text-[11px] uppercase tracking-wider text-gray-400 mb-1">Description</div>
                            <p class="text-sm text-gray-700 dark:text-gray-200">{{ $document->description }}</p>
                        </div>
                    @endif
                </x-card>

                {{-- Concerned staff --}}
                @php
                    $concernedCount = $document->assignees->count();
                    $ackedCount = $document->is_broadcast ? $document->assignees->filter(fn ($p) => $p->pivot->acknowledged_at)->count() : null;
                @endphp
                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">Concerned staff <span class="text-gray-400 font-normal text-sm">(can track this document)</span></h2>
                        <span class="text-xs text-gray-400 shrink-0">
                            {{ $concernedCount }} {{ \Illuminate\Support\Str::plural('person', $concernedCount) }}@if($ackedCount !== null) · {{ $ackedCount }}/{{ $concernedCount }} acknowledged @endif
                        </span>
                    </div>

                    @if($ackedCount !== null && $concernedCount)
                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-3">
                            <div class="h-full rounded-full bg-green-500" style="width: {{ round($ackedCount / $concernedCount * 100) }}%"></div>
                        </div>
                    @endif

                    <div x-data="{ showAll: false }">
                        <div class="flex flex-wrap gap-2">
                            @foreach($document->assignees as $i => $person)
                                <span x-show="showAll || {{ $i }} < 12" @if($i >= 12) x-cloak @endif
                                      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ $person->avatar_url }}" class="w-7 h-7 rounded-full shrink-0">
                                    <span class="leading-tight">
                                        <span class="flex items-center gap-1 text-xs font-medium">
                                            {{ $person->name }}
                                            @if($document->is_broadcast)
                                                @if($person->pivot->acknowledged_at)
                                                    <span class="text-green-500" title="Acknowledged">✓</span>
                                                @else
                                                    <span class="text-amber-500 text-[10px]" title="Not yet acknowledged">●</span>
                                                @endif
                                            @endif
                                        </span>
                                        <span class="block text-[11px] text-gray-400">{{ $person->orgUnit() }}</span>
                                    </span>
                                </span>
                            @endforeach
                        </div>
                        @if($concernedCount > 12)
                            <button type="button" @click="showAll = !showAll" class="text-xs link mt-3"
                                    x-text="showAll ? 'Show fewer' : 'Show all {{ $concernedCount }} →'"></button>
                        @endif
                    </div>
                </x-card>

                {{-- History timeline --}}
                <x-card title="Tracking history">
                    <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-2 space-y-6">
                        @foreach($document->logs as $log)
                            <li class="ml-5">
                                <span class="absolute -left-[7px] w-3.5 h-3.5 rounded-full border-2 border-white dark:border-gray-800" style="background: var(--color-primary)"></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <x-badge :color="$log->actionColor()">{{ $log->actionLabel() }}</x-badge>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->format('M d, Y g:i A') }} · {{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm mt-1">
                                    <span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span>@if($log->actor)<span class="text-xs text-gray-400"> · {{ $log->actor->orgShort() }}</span>@endif
                                    @if($log->toUser)
                                        <span class="text-gray-400">→</span> <span class="font-medium">{{ $log->toUser->name }}</span><span class="text-xs text-gray-400"> · {{ $log->toUser->orgShort() }}</span>
                                    @endif
                                </p>
                                @if($log->remarks)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">“{{ $log->remarks }}”</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            </div>

            {{-- Right: QR + actions --}}
            <div class="space-y-6">
                {{-- QR --}}
                <x-card>
                    <h2 class="font-semibold mb-3 text-center">QR Code</h2>
                    <div class="flex justify-center">
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <img src="{{ route('documents.qrcode', $document) }}" alt="QR" class="w-44 h-44">
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 text-center mt-2 break-all">{{ $trackUrl }}</p>
                    <div class="mt-3">
                        <x-btn :href="route('documents.print', $document)" variant="secondary" class="w-full" target="_blank">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print QR Slip
                        </x-btn>
                    </div>
                </x-card>

                {{-- Action panel (only shown when there is something this user can do) --}}
                @php
                    $u = auth()->user();
                    $canAct = $u->can('assign', $document) || $u->can('release', $document) || $u->can('receive', $document)
                        || $u->can('forward', $document) || $u->can('archive', $document) || $u->can('delete', $document)
                        || $u->can('acknowledge', $document) || $u->can('reopen', $document);
                @endphp
                @if($canAct || $document->isClosed())
                <x-card title="Actions">
                    <div class="space-y-3">

                        @can('acknowledge', $document)
                            <form method="POST" action="{{ route('documents.acknowledge', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                                  data-confirm="Acknowledge that you received this memo?">
                                @csrf
                                <p class="text-xs text-blue-700 dark:text-blue-300">📣 This is a memo broadcast to your {{ $document->division?->name ? 'division' : 'department' }}. Please acknowledge receipt.</p>
                                <x-btn type="submit" class="w-full">✅ Acknowledge receipt</x-btn>
                            </form>
                        @endcan

                        @can('assign', $document)
                            <button @click="panel = panel === 'assign' ? null : 'assign'" class="w-full text-left px-4 py-2 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium hover:opacity-90">Assign / Re-assign</button>
                            <div x-show="panel === 'assign'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.assign', $document) }}" class="space-y-2"
                                      data-confirm="Assign / re-assign this document to the selected staff?">
                                    @csrf
                                    <select name="assignee_id" class="input" required>
                                        <option value="">— Select staff —</option>
                                        @foreach($users->groupBy(fn($u) => $u->department?->code ?? 'No office') as $group => $gu)
                                            <optgroup label="{{ $group }}">
                                                @foreach($gu as $u)<option value="{{ $u->id }}" @selected($document->current_holder_id==$u->id)>{{ $u->name }} — {{ $u->division?->code ?? 'Head' }}</option>@endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                                    <x-btn type="submit" class="w-full">Confirm assignment</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('release', $document)
                            <form method="POST" action="{{ route('documents.release', $document) }}" class="space-y-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20"
                                  data-confirm="Release this document to {{ $document->currentHolder?->name }}? You will then print and attach the QR.">
                                @csrf
                                <p class="text-xs text-amber-700 dark:text-amber-300">Releasing hands the document to <strong>{{ $document->currentHolder?->name }}</strong>. Print and attach the QR.</p>
                                <input type="text" name="remarks" class="input" placeholder="Release remarks (optional)">
                                <x-btn type="submit" variant="primary" class="w-full">🚀 Release Document</x-btn>
                            </form>
                        @endcan

                        @can('receive', $document)
                            @php $desktopReceive = ($settings['allow_desktop_receive'] ?? '0') === '1'; $isClaim = $document->current_holder_id === null; @endphp
                            @if($desktopReceive)
                                {{-- Desktop receive/claim explicitly enabled in settings --}}
                                <form method="POST" action="{{ route('documents.receive', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                                      data-confirm="{{ $isClaim ? 'Claim this document for your office? You will become its holder.' : 'Confirm you physically received this document?' }}">
                                    @csrf
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        @if($isClaim)
                                            📥 This document was <strong>transferred to your office</strong>. Claim it to take responsibility — other receivers will then stop seeing it as unclaimed.
                                        @else
                                            Confirm you physically received this document.
                                        @endif
                                    </p>
                                    <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                                    <x-btn type="submit" variant="primary" class="w-full">{{ $isClaim ? '📥 Claim & Receive' : '✅ Receive Document' }}</x-btn>
                                </form>
                            @else
                                {{-- Default: physical possession is proven by scanning the QR on the document itself --}}
                                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-center space-y-1">
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        @if($isClaim)
                                            📥 This document was <strong>transferred to your office</strong> and is waiting to be claimed.
                                        @else
                                            This document is assigned to you.
                                        @endif
                                    </p>
                                    <p class="text-xs font-medium text-blue-800 dark:text-blue-200">📱 Scan the QR code on the physical document to {{ $isClaim ? 'claim' : 'receive' }} it.</p>
                                </div>
                            @endif
                        @endcan

                        @can('forward', $document)
                            <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full text-left px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:opacity-90">Forward to another staff</button>
                            <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2"
                                      data-confirm="Forward this document to the selected staff?">
                                    @csrf
                                    <select name="to_user_id" class="input" required>
                                        <option value="">— Forward to —</option>
                                        @foreach($users->groupBy(fn($u) => $u->department?->code ?? 'No office') as $group => $gu)
                                            <optgroup label="{{ $group }}">
                                                @foreach($gu as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->division?->code ?? 'Head' }}</option>@endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <textarea name="remarks" rows="2" class="input" placeholder="Details about this action (required)" required></textarea>
                                    <x-btn type="submit" class="w-full">Forward</x-btn>
                                </form>
                            </div>

                            @if($crossDept)
                                <button @click="panel = panel === 'transfer' ? null : 'transfer'" class="w-full text-left px-4 py-2 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-sm font-medium hover:opacity-90">Transfer to another office</button>
                                <div x-show="panel === 'transfer'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                    <form method="POST" action="{{ route('documents.transfer', $document) }}" class="space-y-2"
                                          data-confirm="Transfer this document to the selected office? Their receiving staff will be able to claim it.">
                                        @csrf
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Sends to the office's receiving pool — no specific person. Any receiver there can claim it.</p>
                                        <select name="to_department_id" class="input" required>
                                            <option value="">— Select office —</option>
                                            @foreach($departments as $dept)
                                                @if($dept->id != $document->department_id)
                                                    <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <textarea name="remarks" rows="2" class="input" placeholder="Details about this transfer (required)" required></textarea>
                                        <x-btn type="submit" class="w-full">📤 Transfer to office</x-btn>
                                    </form>
                                </div>
                            @endif
                        @endcan

                        @can('archive', $document)
                            <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full text-left px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium hover:opacity-90">Archive / Complete</button>
                            <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                                      data-confirm="Archive/close this document? This ends its active tracking.">
                                    @csrf
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="completed" value="1" class="rounded text-[color:var(--color-primary)]"> Mark as fully completed
                                    </label>
                                    <textarea name="remarks" rows="2" class="input" placeholder="Completion details (required)" required></textarea>
                                    <x-btn type="submit" variant="success" class="w-full">Archive Document</x-btn>
                                </form>
                            </div>
                        @endcan

                        @if($document->isClosed())
                            <div class="text-center text-sm text-green-600 dark:text-green-400 py-2">✔ This document is {{ $document->status }}.</div>
                            @can('reopen', $document)
                                <form method="POST" action="{{ route('documents.reopen', $document) }}" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40"
                                      data-confirm="Reopen this document and set it back to active?">
                                    @csrf
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Super Admin: reopen if this was completed by mistake.</p>
                                    <x-btn type="submit" variant="secondary" class="w-full">↩ Reopen document</x-btn>
                                </form>
                            @endcan
                        @endif

                        @can('delete', $document)
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" data-confirm="Delete this document permanently?">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full text-center px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm">Delete document</button>
                            </form>
                        @endcan
                    </div>
                </x-card>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
