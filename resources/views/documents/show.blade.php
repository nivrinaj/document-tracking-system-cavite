<x-app-layout>
    <x-slot name="header">Document Details</x-slot>

    <div x-data="{ panel: null }" class="space-y-5">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-semibold">{{ $document->title }}</h1>
                    <span x-data="{ copied: false }" class="inline-flex items-center">
                        <button type="button" @click="navigator.clipboard?.writeText(@js($document->title)); copied = true; setTimeout(() => copied = false, 1500)"
                                class="p-1 rounded text-gray-400 hover:text-[color:var(--color-primary)]" :title="copied ? 'Copied!' : 'Copy title'">
                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6m-6-4V5a2 2 0 012-2h4.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V13a2 2 0 01-2 2h-2"/></svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </span>
                    <x-status-badge :status="$document->status" />
                    <x-priority-badge :priority="$document->priority" />
                </div>
                <div class="mt-2 inline-flex items-center gap-1.5" x-data="{ copied: false }">
                    <span class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-3 pr-2 py-1.5">
                        <span class="text-[10px] font-medium uppercase tracking-wider text-gray-400">Tracking code</span>
                        <span class="font-mono text-base font-semibold tracking-wide text-gray-900 dark:text-white">{{ $document->tracking_code }}</span>
                        <button type="button" @click="navigator.clipboard?.writeText('{{ $document->tracking_code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                class="ml-0.5 p-1 rounded text-gray-400 hover:text-[color:var(--color-primary)]" :title="copied ? 'Copied!' : 'Copy'">
                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6m-6-4V5a2 2 0 012-2h4.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V13a2 2 0 01-2 2h-2"/></svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </span>
                </div>
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
                                <div class="font-semibold">📣 Distributed to multiple people</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Each recipient acknowledges receipt individually</div>
                            @elseif($h && $document->status === 'draft')
                                {{-- Assigned but not released — the encoder still physically holds it. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">Pending release</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Assigned to {{ $h->name }} ({{ $h->orgShort() }}) — not yet handed over</div>
                            @elseif($h && in_array($document->status, ['released', 'forwarded']))
                                {{-- Handed over / sent, but the recipient hasn't confirmed receipt yet. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">Awaiting Receipt</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $document->status === 'forwarded' ? 'Forwarded' : 'Released' }} to {{ $h->name }} ({{ $h->orgShort() }}) — awaiting receipt</div>
                            @elseif($h)
                                {{-- Received (or closed): the holder actually possesses it. --}}
                                <div class="font-semibold">{{ $h->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $h->orgUnit() }}</div>
                                @if($document->is_pending)
                                    <div class="mt-1 inline-flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">⏸ Pending — timer paused</div>
                                @elseif(! $document->isClosed())
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Held for <span class="font-medium">{{ $document->timeWithCurrentHolder() }}</span></div>
                                @endif
                            @elseif($document->status === 'released')
                                {{-- Transferred to an office pool, not yet claimed. --}}
                                <div class="font-semibold text-amber-600 dark:text-amber-400">Awaiting claim</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Transferred to {{ $document->department?->code }} — not yet claimed</div>
                            @else
                                <div class="font-semibold text-gray-500 dark:text-gray-400">Not yet assigned</div>
                                <div class="text-xs text-gray-400">⏳ Awaiting routing</div>
                            @endif
                        </div>
                    </div>

                    {{-- Last status action (most recent movement) --}}
                    @php
                        $last = $document->logs->first();
                        $lastIconBg = [
                            'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                            'purple' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300',
                            'amber' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300',
                            'blue' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300',
                            'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300',
                            'green' => 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-300',
                        ][$last?->actionColor()] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
                    @endphp
                    @if($last)
                        <div class="flex items-center gap-3 mb-6 -mt-2 px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700">
                            <span class="shrink-0 w-9 h-9 rounded-full grid place-items-center {{ $lastIconBg }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] uppercase tracking-wider text-gray-400">Last action</span>
                                    <x-badge :color="$last->actionColor()">{{ $last->actionLabel() }}</x-badge>
                                </div>
                                <p class="text-sm mt-0.5">
                                    <span class="text-gray-700 dark:text-gray-200">by <span class="font-medium">{{ $last->actor?->name ?? 'System' }}</span></span>
                                    <span class="text-gray-400">· {{ $last->created_at->diffForHumans() }}</span>
                                    <span class="block text-[11px] text-gray-400">{{ $last->created_at->format('M d, Y g:i A') }}</span>
                                </p>
                            </div>
                        </div>
                    @endif

                    @php
                        $pausedSecs = $document->totalPausedSeconds();
                        $hasAccounting = $document->fund_id || $document->amount !== null || $document->obr_no || $document->responsibility_center_id || $document->nature_of_transaction;
                        $rcLabel = $document->rcLabel();
                        // This panel visualizes the working-hours window specifically, so it's
                        // hidden (not just recalculated) for a department using calendar-days
                        // tracking — that's the whole point of the mode: hide working hours.
                        $showBreakdown = \App\Models\Setting::get('show_daily_breakdown', '1') === '1'
                            && $document->timeTrackingMode() !== 'calendar_days';
                        $breakdown = $showBreakdown
                            ? \App\Services\BusinessHours::dailyDetail($document->received_at ?? $document->created_at, $document->completed_at ?? now())
                            : [];
                        // Bank-grade detail: one panel per section, clean label/value grid inside.
                        $k = 'field-k';
                        $v = 'mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100 break-words';
                        $card = 'rounded-xl border border-gray-200/90 dark:border-gray-700 bg-white dark:bg-gray-800/40 shadow-sm';
                        $secHdr = 'flex items-center gap-2.5 px-4 sm:px-5 py-3 border-b border-gray-100 dark:border-gray-700/70';
                        $secTitle = 'text-xs font-semibold tracking-wide text-gray-700 dark:text-gray-200';
                        $body = 'p-4 sm:p-5 grid gap-x-6 gap-y-5 [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]';
                        $ico = 'grid place-items-center h-7 w-7 rounded-lg text-white shrink-0 text-[13px]';
                    @endphp

                    <div class="space-y-4">
                        {{-- ── Document ── --}}
                        <section class="{{ $card }}">
                            <header class="{{ $secHdr }}">
                                <span class="{{ $ico }}" style="background: var(--color-primary)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                <h3 class="{{ $secTitle }}">Document</h3>
                            </header>
                            <dl class="{{ $body }}">
                                <div><dt class="{{ $k }}">Type</dt><dd class="{{ $v }}">{{ $document->document_type }}</dd></div>
                                @if($document->is_transmittal)
                                    <div><dt class="{{ $k }}">Transmittal</dt><dd class="mt-1">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-semibold">📄 {{ $document->transmittal_quantity }} {{ \Illuminate\Support\Str::plural($document->document_type, (int) $document->transmittal_quantity) }}</span>
                                    </dd></div>
                                @endif
                                @if($document->voucher_number)
                                    <div><dt class="{{ $k }}">Voucher No.</dt><dd class="{{ $v }} font-mono">{{ $document->voucher_number }}</dd></div>
                                @endif
                                <div><dt class="{{ $k }}">Source / Origin</dt><dd class="{{ $v }}">{{ $document->source ?? '—' }}</dd></div>
                                <div><dt class="{{ $k }}">Current location</dt><dd class="{{ $v }}">{{ $document->department?->code ?? '—' }}@if($document->division) <span class="text-gray-400 font-normal">· {{ $document->division->name }}</span>@endif</dd></div>
                            </dl>
                        </section>

                        {{-- ── Accounting ── --}}
                        @if($hasAccounting)
                            <section class="{{ $card }}">
                                <header class="{{ $secHdr }}">
                                    <span class="{{ $ico }}" style="background: var(--color-primary)">₱</span>
                                    <h3 class="{{ $secTitle }}">Accounting</h3>
                                </header>
                                <dl class="{{ $body }}">
                                    @if($document->amount !== null)
                                        <div><dt class="{{ $k }}">Amount</dt><dd class="mt-1 text-base font-semibold tabular-nums text-gray-900 dark:text-white">₱{{ number_format($document->amount, 2) }}</dd></div>
                                    @endif
                                    @if($document->fund)
                                        <div><dt class="{{ $k }}">Fund</dt><dd class="{{ $v }}">{{ $document->fund->name }} <span class="text-gray-400 font-normal">({{ $document->fund->code }})</span></dd></div>
                                    @endif
                                    @if($document->obr_no)
                                        <div><dt class="{{ $k }}">OBR No.</dt><dd class="{{ $v }} font-mono">{{ $document->obr_no }}</dd></div>
                                    @endif
                                    @if($rcLabel)
                                        <div><dt class="{{ $k }}">Resp. Center</dt><dd class="{{ $v }}">{{ $rcLabel }}</dd></div>
                                    @endif
                                    @if($document->nature_of_transaction)
                                        <div><dt class="{{ $k }}">Nature</dt><dd class="{{ $v }}">{{ $document->nature_of_transaction }}</dd></div>
                                    @endif
                                </dl>
                            </section>
                        @endif

                        {{-- ── Timeline ── --}}
                        <section class="{{ $card }}">
                            <header class="{{ $secHdr }}">
                                <span class="{{ $ico }}" style="background: var(--color-primary)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <h3 class="{{ $secTitle }}">Timeline</h3>
                            </header>
                            <dl class="{{ $body }}">
                                <div><dt class="{{ $k }}">Received</dt><dd class="{{ $v }}">{{ $document->received_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                                <div><dt class="{{ $k }}">Age</dt><dd class="{{ $v }}">{{ $document->age() }}</dd></div>
                                <div><dt class="{{ $k }}">Total paused</dt><dd class="mt-1 text-sm font-medium {{ $pausedSecs > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-800 dark:text-gray-100' }}">{{ $pausedSecs > 0 ? \App\Models\Document::humanDuration($pausedSecs) : '—' }}</dd></div>
                                <div><dt class="{{ $k }}">{{ $document->isClosed() ? 'Turnaround' : 'Idle time' }}</dt>
                                    <dd class="mt-1">
                                        @if($document->isClosed())
                                            <span class="{{ $v }} !mt-0">{{ $document->turnaround() ?? '—' }}</span>
                                        @else
                                            <x-badge :color="$document->agingColor()">{{ $document->elapsedSinceLastAction() }}</x-badge>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        {{-- ── Description (before Daily Working Time) ── --}}
                        @if($document->description)
                            <section class="{{ $card }}">
                                <header class="{{ $secHdr }}">
                                    <span class="{{ $ico }}" style="background: var(--color-primary)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                                    </span>
                                    <h3 class="{{ $secTitle }}">Description</h3>
                                </header>
                                <div class="p-4 sm:p-5 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ $document->description }}</div>
                            </section>
                        @endif

                        {{-- ── Daily Working Time (Super-Admin toggle) ── --}}
                        @if($showBreakdown && count($breakdown))
                            <section class="{{ $card }}">
                                <header class="{{ $secHdr }}">
                                    <span class="{{ $ico }}" style="background: var(--color-primary)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6m6 13v-9m-12 9V9m18 10V3"/></svg>
                                    </span>
                                    <h3 class="{{ $secTitle }}">Daily Working Time</h3>
                                </header>
                                <div class="p-4 sm:p-5 space-y-3">
                                    @foreach($breakdown as $date => $info)
                                        @php
                                            $daySpan = max(1, $info['day_start']->diffInSeconds($info['day_end']));
                                            $offsetPct = max(0, min(100, $info['day_start']->diffInSeconds($info['from']) / $daySpan * 100));
                                            $widthPct = max(0, min(100 - $offsetPct, $info['from']->diffInSeconds($info['to']) / $daySpan * 100));
                                        @endphp
                                        <div class="flex items-center gap-3">
                                            <div class="w-20 sm:w-24 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300">{{ \Carbon\Carbon::parse($date)->format('D, M d') }}</div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between text-[11px] text-gray-400 mb-1">
                                                    <span class="tabular-nums">{{ $info['from']->format('g:i A') }} – {{ $info['to']->format('g:i A') }}</span>
                                                    <span class="font-medium text-gray-600 dark:text-gray-300 tabular-nums">{{ \App\Models\Document::humanDuration($info['seconds']) }}</span>
                                                </div>
                                                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
                                                    <div class="h-full rounded-full absolute top-0" style="left: {{ $offsetPct }}%; width: {{ $widthPct }}%; background: var(--color-primary)"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        {{-- ── Total time per user (only meaningful once the document is finished) ── --}}
                        @if($document->isClosed())
                            @php $holdingSummary = $document->userHoldingSummary(); @endphp
                            @if($holdingSummary->isNotEmpty())
                                <section class="{{ $card }}">
                                    <header class="{{ $secHdr }}">
                                        <span class="{{ $ico }}" style="background: var(--color-primary)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-1a4 4 0 10-4-4"/></svg>
                                        </span>
                                        <h3 class="{{ $secTitle }}">Total Time per Staff</h3>
                                    </header>
                                    <p class="px-4 sm:px-5 pt-3 text-[11px] text-gray-400">Combined time each person held this document, added up across every time it came back to them.@if($document->timeTrackingMode() === 'calendar_days') Counted in <strong>calendar days</strong>@unless($document->calendarDaysIncludesWeekends()) (weekends excluded)@endunless, per this document's setting.@endif</p>
                                    <div class="p-4 sm:p-5 space-y-2.5">
                                        @foreach($holdingSummary as $row)
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <img src="{{ $row['user']->avatar_url }}" class="w-7 h-7 rounded-full shrink-0">
                                                    <span class="text-sm truncate">{{ $row['user']->name }}</span>
                                                </div>
                                                <span class="text-sm font-semibold tabular-nums shrink-0">{{ \App\Models\Document::humanDuration($row['seconds']) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endif
                    </div>
                </x-card>

                {{-- Route slip items (when enabled and present) --}}
                @if(\App\Models\Document::routeItemsEnabled() && $document->items->isNotEmpty())
                    @php
                        $itemsCleared = $document->items->where('status', 'cleared')->count();
                        $itemsRejected = $document->items->where('status', 'rejected')->count();
                        $itemsPending = $document->items->where('status', 'pending')->count();
                        $canDecideItems = auth()->user()->can('archive', $document) || auth()->user()->can('forward', $document);
                    @endphp
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">Route slip items <span class="text-gray-400 font-normal text-sm">({{ $document->items->count() }})</span></h2>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">● {{ $itemsCleared }} cleared</span>
                                <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">● {{ $itemsRejected }} rejected</span>
                                <span class="inline-flex items-center gap-1 text-gray-400">● {{ $itemsPending }} pending</span>
                            </div>
                        </div>
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($document->items as $item)
                                <li class="py-3" x-data="{ rejecting: false }">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-medium text-sm">{{ $item->title }}</span>
                                                <x-badge :color="$item->statusColor()">{{ $item->statusLabel() }}</x-badge>
                                            </div>
                                            @if($item->decided_at)
                                                <p class="text-[11px] text-gray-400 mt-0.5">by {{ $item->decider?->name ?? 'System' }} · {{ $item->decided_at->diffForHumans() }}</p>
                                            @endif
                                            @if($item->remarks)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">“{{ $item->remarks }}”</p>
                                            @endif
                                        </div>
                                        @if($canDecideItems && $item->status === 'pending')
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <form method="POST" action="{{ route('documents.items.decision', [$document, $item]) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="cleared">
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-medium hover:opacity-90" title="Mark cleared">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                        Clear
                                                    </button>
                                                </form>
                                                <button type="button" @click="rejecting = !rejecting" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium hover:opacity-90" title="Reject & return to origin">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Reject
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    @if($canDecideItems && $item->status === 'pending')
                                        <form method="POST" action="{{ route('documents.items.decision', [$document, $item]) }}" x-show="rejecting" x-cloak class="mt-2 flex gap-2" data-confirm="Reject this item and flag it for return to origin?">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <input type="text" name="remarks" class="input" placeholder="Reason for rejection (required)" required>
                                            <x-btn type="submit" variant="danger" class="shrink-0">Reject &amp; return</x-btn>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                {{-- Concerned staff --}}
                @php
                    $concernedCount = $document->assignees->count();
                    // Only people explicitly asked to acknowledge are counted/tracked for acks.
                    $requested = $document->assignees->filter(fn ($p) => $p->pivot->ack_requested_at);
                    $ackRequestedCount = $requested->count();
                    $ackedCount = $ackRequestedCount ? $requested->filter(fn ($p) => $p->pivot->acknowledged_at)->count() : null;
                    // The instant timers stop when the document is paused (pending).
                    $clockUntil = ($document->is_pending && $document->pending_at) ? $document->pending_at : now();
                    // Total possession time per holder (from the ledger), counted in
                    // working hours so time held outside office hours isn't charged.
                    $heldSeconds = [];
                    foreach ($document->possessions as $seg) {
                        if ($seg->holder_id) {
                            $segEnd = $seg->ended_at ?? $clockUntil;
                            $heldSeconds[$seg->holder_id] = ($heldSeconds[$seg->holder_id] ?? 0)
                                + $document->elapsedSeconds($seg->started_at, $segEnd, $seg->holder);
                        }
                    }
                    // Who physically holds it right now (open ledger segment). During transit
                    // this is the sender — it's their duty until the recipient receives.
                    $possessorId = optional($document->possessions->firstWhere('ended_at', null))->holder_id;

                    // Tabbed acknowledgment layout: only for broadcasts, and only when the
                    // broadcasting office opted in (DB flag, never matched by name/code).
                    $useAckTabs = $document->is_broadcast && optional($document->department)->broadcast_ack_layout;
                    $ackGroups = [];
                    if ($useAckTabs) {
                        $order = array_merge(\App\Models\User::EMPLOYMENT_STATUSES, ['Unspecified']);
                        $byStatus = $document->assignees->groupBy(fn ($p) => $p->employment_status ?: 'Unspecified');
                        foreach ($order as $status) {
                            if (! isset($byStatus[$status])) {
                                continue;
                            }
                            // Group each status by division, then sort each division: people who
                            // have NOT yet received/acknowledged first, then alphabetically by surname.
                            $ackGroups[$status] = $byStatus[$status]
                                ->groupBy(fn ($p) => $p->division?->name ?: 'Department Heads')
                                // Heads (no division) first, then divisions alphabetically.
                                ->sortBy(fn ($g, $div) => $div === 'Department Heads' ? '0' : '1'.mb_strtolower($div))
                                ->map(fn ($g) => $g->sort(function ($a, $b) {
                                    $aAck = $a->pivot->acknowledged_at ? 1 : 0;
                                    $bAck = $b->pivot->acknowledged_at ? 1 : 0;
                                    return $aAck !== $bAck
                                        ? $aAck <=> $bAck
                                        : strcasecmp($a->last_name ?: $a->name, $b->last_name ?: $b->name);
                                })->values());
                        }
                    }
                @endphp
                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">Concerned staff <span class="text-gray-400 font-normal text-sm">(can track this document)</span></h2>
                        <span class="text-xs text-gray-400 shrink-0">
                            {{ $concernedCount }} {{ \Illuminate\Support\Str::plural('person', $concernedCount) }}@if($ackedCount !== null) · {{ $ackedCount }}/{{ $ackRequestedCount }} acknowledged @endif
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-3 -mt-1">Time held is how long each person physically had the document; for people asked to acknowledge, it's how long until they did (or how long they've been waited on).@if($document->is_pending) <span class="text-amber-600 dark:text-amber-400">Timers are paused while this document is pending.</span>@endif</p>

                    @if($ackedCount !== null && $ackRequestedCount)
                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-3">
                            <div class="h-full rounded-full bg-green-500" style="width: {{ round($ackedCount / $ackRequestedCount * 100) }}%"></div>
                        </div>
                    @endif

                    @if($useAckTabs)
                        <div x-data="{ tab: '{{ array_key_first($ackGroups) }}' }">
                            <div class="flex flex-wrap gap-1.5 mb-4 border-b border-gray-100 dark:border-gray-700">
                                @foreach($ackGroups as $status => $divs)
                                    @php
                                        $statusTotal = $divs->flatten(1)->count();
                                        $statusAcked = $divs->flatten(1)->filter(fn ($p) => $p->pivot->acknowledged_at)->count();
                                    @endphp
                                    <button type="button" @click="tab = @js((string) $status)"
                                            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                                            :class="tab === @js((string) $status) ? 'border-[color:var(--color-primary)] text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)]' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                                        {{ $status }} <span class="text-xs text-gray-400">({{ $statusAcked }}/{{ $statusTotal }})</span>
                                    </button>
                                @endforeach
                            </div>

                            @foreach($ackGroups as $status => $divs)
                                <div x-show="tab === @js((string) $status)" x-cloak class="space-y-4">
                                    @foreach($divs as $divName => $people)
                                        @php $divAcked = $people->filter(fn ($p) => $p->pivot->acknowledged_at)->count(); @endphp
                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">{{ $divName }} <span class="text-gray-300 dark:text-gray-600">· {{ $divAcked }}/{{ $people->count() }} received</span></div>
                                            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                                                <table class="w-full table-fixed text-sm">
                                                    <colgroup>
                                                        <col style="width:45%">
                                                        <col style="width:25%">
                                                        <col style="width:30%">
                                                    </colgroup>
                                                    <thead class="bg-gray-50 dark:bg-gray-700/40 text-[11px] uppercase tracking-wide text-gray-400">
                                                        <tr>
                                                            <th class="text-left font-medium px-3 py-2">Name</th>
                                                            <th class="text-left font-medium px-3 py-2">Position</th>
                                                            <th class="text-left font-medium px-3 py-2">Date &amp; time received</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                        @foreach($people as $person)
                                                            @php $acked = $person->pivot->acknowledged_at ? \Illuminate\Support\Carbon::parse($person->pivot->acknowledged_at) : null; @endphp
                                                            <tr class="{{ $acked ? '' : 'bg-amber-50/50 dark:bg-amber-900/10' }}">
                                                                <td class="px-3 py-2 font-medium truncate" title="{{ $person->formalName() }}">{{ $person->formalName() }}</td>
                                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 truncate" title="{{ $person->position }}">{{ $person->position ?: '—' }}</td>
                                                                <td class="px-3 py-2 truncate">
                                                                    @if($acked)
                                                                        <span class="text-green-600 dark:text-green-400">{{ $acked->format('M d, Y g:i A') }}</span>
                                                                    @else
                                                                        <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span> Not yet received</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                    <div x-data="{ showAll: false }">
                        <div class="flex flex-wrap gap-2">
                            @foreach($document->assignees as $i => $person)
                                <span x-show="showAll || {{ $i }} < 12" @if($i >= 12) x-cloak @endif
                                      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ $person->avatar_url }}" class="w-7 h-7 rounded-full shrink-0">
                                    <span class="leading-tight">
                                        <span class="flex items-center gap-1 text-xs font-medium">
                                            {{ $person->name }}
                                            @if($person->pivot->ack_requested_at)
                                                @if($person->pivot->acknowledged_at)
                                                    <span class="text-green-500" title="Acknowledged">✓</span>
                                                @else
                                                    <span class="text-amber-500 text-[10px]" title="Asked to acknowledge — not yet">●</span>
                                                @endif
                                            @endif
                                        </span>
                                        <span class="block text-[11px] text-gray-400">{{ $person->orgUnit() }}</span>
                                        @if($person->pivot->ack_requested_at)
                                            @php $sentAt = \Illuminate\Support\Carbon::parse($person->pivot->ack_requested_at); @endphp
                                            @if($person->pivot->acknowledged_at)
                                                <span class="block text-[11px] text-green-600 dark:text-green-400">✓ acknowledged in {{ \App\Models\Document::humanDuration((int) $sentAt->diffInSeconds($person->pivot->acknowledged_at)) }}</span>
                                            @else
                                                <span class="block text-[11px] text-amber-600 dark:text-amber-400">⏱ waiting {{ \App\Models\Document::humanDuration((int) $sentAt->diffInSeconds($clockUntil)) }}{{ $document->is_pending ? ' (paused)' : '' }}</span>
                                            @endif
                                        @elseif(isset($heldSeconds[$person->id]))
                                            @php $isHoldingNow = $possessorId === $person->id && !$document->is_pending && !$document->isClosed(); @endphp
                                            <span class="block text-[11px] {{ $isHoldingNow ? 'text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)] font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                                ⏱ {{ \App\Models\Document::humanDuration($heldSeconds[$person->id]) }}{{ $isHoldingNow ? ' (holding now)' : '' }}
                                            </span>
                                        @endif
                                    </span>
                                </span>
                            @endforeach
                        </div>
                        @if($concernedCount > 12)
                            <button type="button" @click="showAll = !showAll" class="text-xs link mt-3"
                                    x-text="showAll ? 'Show fewer' : 'Show all {{ $concernedCount }} →'"></button>
                        @endif
                    </div>
                    @endif
                </x-card>

                {{-- Digital Copy (the encoder's digitized original) --}}
                @if(\App\Models\Document::digitalCopyEnabled())
                    @php $dc = $document->digitalCopy; $canDigital = ! $document->isClosed() && auth()->id() === $document->created_by; @endphp
                    <x-card>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-8 h-8 rounded-lg grid place-items-center bg-[color:var(--color-primary)]/10 dark:bg-[color:var(--color-primary)]/25 text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </span>
                            <div>
                                <h2 class="font-semibold leading-tight">Digital Copy</h2>
                                <p class="text-[11px] text-gray-400 leading-tight">The encoder's digitized original</p>
                            </div>
                        </div>

                        @if($dc)
                            <div class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <a href="{{ route('attachments.download', $dc) }}" target="_blank" class="min-w-0 flex-1 group flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="min-w-0"><span class="block text-sm font-medium group-hover:underline">View digital copy</span><span class="block text-[11px] text-gray-400">{{ $dc->humanSize() }} · {{ $dc->created_at->diffForHumans() }}</span></span>
                                </a>
                                @if($canDigital)
                                    <form method="POST" action="{{ route('attachments.destroy', $dc) }}" data-confirm="Remove the digital copy?">
                                        @csrf @method('DELETE')
                                        <button class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-red-500" title="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if($canDigital)
                            <form method="POST" action="{{ route('attachments.digitalCopy', $document) }}" enctype="multipart/form-data" class="space-y-2 {{ $dc ? 'mt-3' : '' }}" x-data="{ mode: 'pdf' }">
                                @csrf
                                <div class="flex gap-1 text-xs">
                                    <button type="button" @click="mode='pdf'" :class="mode==='pdf' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-3 py-1.5 rounded-lg font-medium">Browse PDF File</button>
                                    <button type="button" @click="mode='camera'" :class="mode==='camera' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-3 py-1.5 rounded-lg font-medium">Capture 📷</button>
                                </div>
                                <div x-show="mode==='pdf'"><x-file-drop name="pdf" accept="application/pdf" label="Browse a PDF (max 2 MB)" /></div>
                                <div x-show="mode==='camera'" x-cloak><x-file-drop name="images[]" accept="image/*" :multiple="true" :capture="true" icon="camera" label="Capture the document" /></div>
                                <x-btn type="submit" class="w-full">{{ $dc ? 'Replace digital copy' : 'Upload digital copy' }}</x-btn>
                            </form>
                        @elseif(! $dc)
                            <p class="text-sm text-gray-400">No digital copy uploaded.</p>
                        @endif
                    </x-card>
                @endif

                {{-- Supporting Documents (title required; file optional; drive the handover checklist) --}}
                @if(\App\Models\Document::attachmentsEnabled())
                    @php
                        $canAttach = ! $document->isClosed() && (
                            ($document->status === 'draft' && auth()->id() === $document->created_by)
                            || ($document->status === 'received' && auth()->id() === $document->current_holder_id)
                        );
                    @endphp
                    <x-card>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">Supporting Documents <span class="text-gray-400 font-normal text-sm">({{ $document->supportingDocuments->count() }})</span></h2>
                            <span class="text-[11px] text-gray-400">ticked on hand-over</span>
                        </div>

                        @forelse($document->supportingDocuments as $att)
                            <div class="flex items-center gap-3 py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                                <span class="shrink-0 w-9 h-9 rounded-lg grid place-items-center {{ $att->hasFile() ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-sm truncate">{{ $att->title }}
                                        @if($att->hasFile())<a href="{{ route('attachments.download', $att) }}" target="_blank" class="text-[11px] link ml-1">view</a>@else<span class="text-[11px] text-gray-400 ml-1">(no file)</span>@endif
                                    </div>
                                    <div class="text-[11px] text-gray-400">@if($att->hasFile()){{ $att->humanSize() }} · @endif by {{ $att->uploader?->name ?? '—' }} · {{ $att->created_at->diffForHumans() }}</div>
                                </div>
                                @if($canAttach)
                                    <form method="POST" action="{{ route('attachments.destroy', $att) }}" data-confirm="Remove this supporting document?">
                                        @csrf @method('DELETE')
                                        <button class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No supporting documents yet.</p>
                        @endforelse

                        @if($canAttach)
                            <div x-data="{ mode: 'none' }" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <form method="POST" action="{{ route('attachments.store', $document) }}" enctype="multipart/form-data" class="space-y-2">
                                    @csrf
                                    <input type="text" name="title" class="input" placeholder="Supporting document title (required)" required maxlength="150">
                                    <div class="flex gap-1 text-xs">
                                        <button type="button" @click="mode='none'" :class="mode==='none' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">Title only</button>
                                        <button type="button" @click="mode='pdf'" :class="mode==='pdf' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">Browse PDF File</button>
                                        <button type="button" @click="mode='camera'" :class="mode==='camera' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">Capture 📷</button>
                                    </div>
                                    <div x-show="mode==='pdf'" x-cloak><x-file-drop name="pdf" accept="application/pdf" label="Browse a PDF (max 2 MB)" /></div>
                                    <div x-show="mode==='camera'" x-cloak><x-file-drop name="images[]" accept="image/*" :multiple="true" :capture="true" icon="camera" label="Capture page(s) — cover first" /></div>
                                    <p class="text-[11px] text-gray-400" x-show="mode==='none'" x-cloak>A title-only item (no file) — for a physical document not yet scanned.</p>
                                    <x-btn type="submit" class="w-full">Add supporting document</x-btn>
                                </form>
                            </div>
                        @endif
                    </x-card>
                @endif

                {{-- Related documents --}}
                @if(\App\Models\Document::linkingEnabled())
                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold">Related documents <span class="text-gray-400 font-normal text-sm">({{ $document->relatedDocuments->count() }})</span></h2>
                    </div>
                    @if($document->relatedDocuments->isNotEmpty())
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700 mb-3">
                            @foreach($document->relatedDocuments as $rel)
                                <li class="flex items-center justify-between gap-3 py-2">
                                    <a href="{{ route('documents.show', $rel) }}" class="min-w-0 group">
                                        <div class="font-medium text-sm truncate group-hover:underline">{{ $rel->title }}</div>
                                        <div class="text-xs text-gray-400 truncate"><span class="font-mono">{{ $rel->tracking_code }}</span> · <x-status-badge :status="$rel->status" /></div>
                                    </a>
                                    <form method="POST" action="{{ route('documents.unlink', [$document, $rel]) }}" data-confirm="Remove this link?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="Remove link">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-400 mb-3">No linked documents yet.</p>
                    @endif
                    <form method="POST" action="{{ route('documents.link', $document) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="tracking_code" class="input font-mono" placeholder="Link by tracking code (e.g. {{ \App\Models\Document::trackingPrefix() }}-{{ date('Y') }}-XXXX)">
                        <x-btn type="submit" variant="secondary" class="shrink-0">🔗 Link</x-btn>
                    </form>
                    <p class="text-xs text-gray-400 mt-1.5">You can only link documents you have access to — your own office, or ones that already concern you.</p>
                </x-card>
                @endif

                {{-- History timeline — collapses when long --}}
                @php $logCount = $document->logs->count(); $logCollapse = 5; @endphp
                <x-card>
                    <div x-data="{ all: {{ $logCount <= $logCollapse ? 'true' : 'false' }} }">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-semibold">Tracking history <span class="text-gray-400 font-normal text-sm">({{ $logCount }})</span></h2>
                            @if($logCount > $logCollapse)
                                <button type="button" @click="all = !all" class="text-xs link" x-text="all ? 'Show recent only' : 'Show all {{ $logCount }} →'"></button>
                            @endif
                        </div>
                        <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-2 space-y-6">
                            @foreach($document->logs as $i => $log)
                                <li class="ml-5" @if($i >= $logCollapse) x-show="all" x-cloak @endif>
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
                        @if($logCount > $logCollapse)
                            <button type="button" @click="all = !all" x-show="!all" class="mt-4 ml-2 text-xs link">Show all {{ $logCount }} entries →</button>
                        @endif
                    </div>
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
                        || $u->can('forward', $document) || $u->can('transfer', $document) || $u->can('archive', $document)
                        || $u->can('pending', $document) || $u->can('resume', $document) || $u->can('delete', $document)
                        || $u->can('distribute', $document) || $u->can('acknowledge', $document) || $u->can('reopen', $document)
                        || $u->can('forwardToHead', $document) || $u->can('claimFromHead', $document);
                @endphp
                @if($canAct || $document->isClosed())
                <x-card title="Actions">
                    <div class="space-y-3">

                        @can('acknowledge', $document)
                            @php $desktopAck = \App\Models\Setting::desktopReceiveAllowedFor(auth()->user()->department_id); @endphp
                            @if($desktopAck)
                                <form method="POST" action="{{ route('documents.acknowledge', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                                      data-confirm="Acknowledge that you have received this document?">
                                    @csrf
                                    <p class="text-xs text-blue-700 dark:text-blue-300">🔔 This document was <strong>distributed to you</strong>. Please acknowledge receipt.</p>
                                    <x-btn type="submit" class="w-full">✅ Acknowledge receipt</x-btn>
                                </form>
                            @else
                                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-center space-y-1">
                                    <p class="text-xs text-blue-700 dark:text-blue-300">🔔 This document was <strong>distributed to you</strong> for acknowledgement.</p>
                                    <p class="text-xs font-medium text-blue-800 dark:text-blue-200">📱 Scan the QR code on the physical document to acknowledge it.</p>
                                </div>
                            @endif
                        @endcan

                        @can('assign', $document)
                            <button @click="panel = panel === 'assign' ? null : 'assign'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                Assign / Re-assign
                            </button>
                            <div x-show="panel === 'assign'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.assign', $document) }}" class="space-y-2"
                                      data-confirm="Assign / re-assign this document to the selected staff?">
                                    @csrf
                                    <select name="assignee_id" class="input" required>
                                        <option value="">— Select staff —</option>
                                        @foreach($users->where('id', '!=', $document->current_holder_id)->groupBy(fn($u) => $u->department?->code ?? 'No office') as $group => $gu)
                                            <optgroup label="{{ $group }}">
                                                @foreach($gu as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->division?->code ?? 'Head' }}</option>@endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                                    <x-btn type="submit" class="w-full">Confirm assignment</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('release', $document)
                            @php $hasAtt = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty(); $reqN = $hasAtt ? $document->supportingDocuments->count() + 1 : 0; @endphp
                            <form method="POST" action="{{ route('documents.release', $document) }}" class="space-y-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20" x-data="{ present: [] }"
                                  data-confirm="Release this document to {{ $document->currentHolder?->name }}? You will then print and attach the QR.">
                                @csrf
                                <p class="text-xs text-amber-700 dark:text-amber-300">Releasing hands the document to <strong>{{ $document->currentHolder?->name }}</strong>. Print and attach the QR.</p>
                                @if($hasAtt)
                                    <p class="text-xs font-medium text-amber-800 dark:text-amber-200">Tick each item you have physically attached:</p>
                                    @include('documents._checklist')
                                @endif
                                <input type="text" name="remarks" class="input" placeholder="Release remarks (optional)">
                                <x-btn type="submit" variant="primary" class="w-full" x-bind:disabled="present.length < {{ $reqN }}" ::class="present.length < {{ $reqN }} ? 'opacity-50 pointer-events-none' : ''">🚀 Release Document</x-btn>
                            </form>
                        @endcan

                        @can('receive', $document)
                            @php $desktopReceive = \App\Models\Setting::desktopReceiveAllowedFor(auth()->user()->department_id); $isClaim = $document->current_holder_id === null; @endphp
                            @php
                                $latestLog = $document->logs->first();
                                $isReturned = $latestLog && $latestLog->action === 'rejected';
                                $hasAttR = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty() && ! $isReturned;
                                $reqR = $hasAttR ? $document->supportingDocuments->count() + 1 : 0;
                            @endphp
                            @if($desktopReceive)
                                {{-- Desktop receive/claim explicitly enabled in settings --}}
                                <div class="p-3 rounded-lg {{ $isReturned ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20' }} space-y-2" x-data="{ present: [] }">
                                    @if($isReturned)
                                        <div class="text-xs text-red-700 dark:text-red-300">
                                            ✗ <strong>Rejected and returned to you</strong>@if($latestLog->actor) by {{ $latestLog->actor->name }}@endif.
                                            @if($latestLog->remarks)<span class="block mt-0.5">Reason: “{{ $latestLog->remarks }}”</span>@endif
                                            <span class="block mt-1 text-red-600 dark:text-red-400">Receiving it back acknowledges the rejection so you can handle the issue internally.</span>
                                        </div>
                                    @else
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            @if($isClaim)
                                                📥 This document was <strong>transferred to your office</strong>. Claim it to take responsibility.
                                            @elseif($hasAttR)
                                                Physically check each item below, then tick it. Accept only if everything is present; otherwise reject.
                                            @else
                                                Confirm you physically received this document.
                                            @endif
                                        </p>
                                    @endif
                                    @if($hasAttR)
                                        @include('documents._checklist')
                                    @endif
                                    <form method="POST" action="{{ route('documents.receive', $document) }}" class="space-y-2"
                                          data-confirm="{{ $isClaim ? 'Claim this document for your office?' : 'Confirm you physically received this document?' }}">
                                        @csrf
                                        <template x-for="id in present" :key="id"><input type="hidden" name="present[]" :value="id"></template>
                                        <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                                        <x-btn type="submit" variant="primary" class="w-full" x-bind:disabled="present.length < {{ $reqR }}" ::class="present.length < {{ $reqR }} ? 'opacity-50 pointer-events-none' : ''">{{ $isClaim ? '📥 Claim & Receive' : ($isReturned ? '✅ Receive back' : '✅ Accept & Receive') }}</x-btn>
                                    </form>
                                    @if($hasAttR)
                                        <form method="POST" action="{{ route('documents.reject', $document) }}" class="space-y-2 pt-2 border-t border-blue-100 dark:border-blue-900/40"
                                              data-confirm="Reject and return this document to the sender?">
                                            @csrf
                                            <input type="text" name="remarks" class="input" placeholder="What's missing / wrong? (required)" required>
                                            <x-btn type="submit" variant="danger" class="w-full">✗ Reject &amp; return to sender</x-btn>
                                        </form>
                                    @endif
                                </div>
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

                        @can('claimFromHead', $document)
                            @php $waitLabel = \App\Models\Document::humanDuration($document->headQueueWaitSeconds()); @endphp
                            <form method="POST" action="{{ route('documents.claimFromHead', $document) }}"
                                  data-confirm="Claim this document from the Department Head? It will show as currently with you.">
                                @csrf
                                <div class="p-3 rounded-lg bg-violet-50 dark:bg-violet-900/20 space-y-2">
                                    <p class="text-xs text-violet-700 dark:text-violet-300">
                                        📋 This document was forwarded to <strong>{{ $document->departmentHead()?->name }}</strong> (Department Head) and is waiting in the queue — <strong>{{ $waitLabel }}</strong> so far.
                                        Any staff in your office may pick it up.
                                    </p>
                                    <x-btn type="submit" class="w-full">Get from Department Head</x-btn>
                                </div>
                            </form>
                        @endcan

                        @can('forward', $document)
                            <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l4-4m0 0l4 4M7 4v12m4 4h6a2 2 0 002-2V8"/></svg>
                                Forward to another staff
                            </button>
                            <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                @php $hasAttF = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty(); $reqF = $hasAttF ? $document->supportingDocuments->count() + 1 : 0; @endphp
                                <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2" x-data="{ present: [] }"
                                      data-confirm="Forward this document to the selected staff?">
                                    @csrf
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Forwards within <strong>your own office</strong> only.</p>
                                    <x-search-select name="to_user_id" placeholder="— Forward to —"
                                        :options="$users->where('id', '!=', $document->current_holder_id)->map(fn($u) => ['value' => $u->id, 'label' => $u->name.' — '.($u->division?->code ?? 'Head'), 'group' => $u->department?->code ?? ''])->values()" />
                                    @if($hasAttF)
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Confirm each item is physically attached:</p>
                                        @include('documents._checklist')
                                    @endif
                                    <textarea name="remarks" rows="2" class="input" placeholder="Details about this action (required)" required></textarea>
                                    <x-btn type="submit" class="w-full" x-bind:disabled="present.length < {{ $reqF }}" ::class="present.length < {{ $reqF }} ? 'opacity-50 pointer-events-none' : ''">Forward</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('forwardToHead', $document)
                            <button @click="panel = panel === 'forwardhead' ? null : 'forwardhead'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-1a4 4 0 10-4-4"/></svg>
                                Forward to Department Head
                            </button>
                            <div x-show="panel === 'forwardhead'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.forwardToHead', $document) }}" class="space-y-2"
                                      data-confirm="Forward this document to {{ $document->departmentHead()?->name }} (Department Head)? Any staff in your office will be able to claim it from the queue.">
                                    @csrf
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Goes to <strong>{{ $document->departmentHead()?->name }}</strong>. Any staff in your office can also pick it up with “Get from Department Head” — whoever acts first becomes the holder.</p>
                                    <textarea name="remarks" rows="2" class="input" placeholder="Details about this action (optional)"></textarea>
                                    <x-btn type="submit" class="w-full">Forward to Department Head</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('transfer', $document)
                            @if($crossDept)
                                <button @click="panel = panel === 'transfer' ? null : 'transfer'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-sm font-medium hover:opacity-90 transition">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Transfer to another office
                                </button>
                                <div x-show="panel === 'transfer'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                    @php $hasAttT = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty(); $reqT = $hasAttT ? $document->supportingDocuments->count() + 1 : 0; @endphp
                                    <form method="POST" action="{{ route('documents.transfer', $document) }}" class="space-y-2" x-data="{ present: [] }"
                                          data-confirm="Transfer this document to the selected office? Their receiving staff will be able to claim it.">
                                        @csrf
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Sends to the office's receiving pool — no specific person. Any receiver there can claim it.</p>
                                        <x-search-select name="to_department_id" placeholder="— Select office —"
                                            :options="$departments->where('id', '!=', $document->department_id)->map(fn($d) => ['value' => $d->id, 'label' => $d->code.' — '.$d->name])->values()" />
                                        @if($hasAttT)
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Confirm each item is physically attached:</p>
                                            @include('documents._checklist')
                                        @endif
                                        <textarea name="remarks" rows="2" class="input" placeholder="Details about this transfer (required)" required></textarea>
                                        <x-btn type="submit" class="w-full" x-bind:disabled="present.length < {{ $reqT }}" ::class="present.length < {{ $reqT }} ? 'opacity-50 pointer-events-none' : ''">📤 Transfer to office</x-btn>
                                    </form>
                                </div>
                            @endif
                        @endcan

                        @can('pending', $document)
                            <button @click="panel = panel === 'pending' ? null : 'pending'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Mark as pending
                            </button>
                            <div x-show="panel === 'pending'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.pending', $document) }}" class="space-y-2"
                                      data-confirm="Mark this document as pending? Your time will pause until it is resumed or received elsewhere.">
                                    @csrf
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Pauses <strong>your</strong> processing time (awaiting action of the origin / someone else). It drops out of the “aging” report while pending.</p>
                                    @if($crossDept)
                                        <label class="label">Return to office <span class="text-gray-400 text-xs">(optional)</span></label>
                                        <select name="return_department_id" class="input">
                                            <option value="">— Keep it with me, just pause the timer —</option>
                                            @foreach($departments as $dept)
                                                @if($dept->id != $document->department_id)
                                                    <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-400">If you pick an office, it goes back to them — and the clock starts against them once they receive it.</p>
                                    @endif
                                    <textarea name="remarks" rows="2" class="input" placeholder="Why is this pending? (required)" required></textarea>
                                    <x-btn type="submit" class="w-full">⏸ Mark pending</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('resume', $document)
                            <form method="POST" action="{{ route('documents.resume', $document) }}" class="space-y-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20"
                                  data-confirm="Resume work on this document? The timer will start again.">
                                @csrf
                                <p class="text-xs text-amber-700 dark:text-amber-300">⏸ This document is <strong>pending</strong>. Resume to start your processing timer again.</p>
                                <textarea name="remarks" rows="2" class="input" placeholder="What changed / why resume now? (required)" required></textarea>
                                <x-btn type="submit" variant="primary" class="w-full">▶ Resume work</x-btn>
                            </form>
                        @endcan

                        @can('distribute', $document)
                            @php
                                $alreadyAsked = $document->assignees->filter(fn ($p) => $p->pivot->ack_requested_at)->pluck('id');
                                $distributablePeople = $users->whereNotIn('id', $alreadyAsked);
                                $hasDistributed = $alreadyAsked->isNotEmpty();
                            @endphp
                            <button @click="panel = panel === 'distribute' ? null : 'distribute'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/></svg>
                                {{ $hasDistributed ? 'Distribute to more people' : 'Distribute for acknowledgement' }}
                            </button>
                            <div x-show="panel === 'distribute'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40"
                                 x-data="{
                                    scope: 'selected',
                                    search: '',
                                    picked: [],
                                    people: @js($distributablePeople->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'division' => $p->division?->code ?? 'Head'])->values()),
                                    get filtered() { const q = this.search.toLowerCase().trim(); return this.people.filter(p => !q || p.name.toLowerCase().includes(q)); },
                                    toggle(id) { const i = this.picked.indexOf(id); if (i === -1) this.picked.push(id); else this.picked.splice(i,1); },
                                 }">
                                <form method="POST" action="{{ route('documents.distribute', $document) }}" class="space-y-2"
                                      data-confirm="Distribute this document to the selected recipients for acknowledgement?">
                                    @csrf
                                    @if($hasDistributed)
                                        <p class="text-xs text-sky-700 dark:text-sky-300">Already sent to {{ $alreadyAsked->count() }} {{ \Illuminate\Support\Str::plural('person', $alreadyAsked->count()) }}. People already asked are no longer listed below.</p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Send this document to people in <strong>your office</strong> to acknowledge — selected staff (across divisions), a whole division, or the entire department. You still keep the physical document.</p>
                                    <select name="scope" x-model="scope" class="input">
                                        <option value="selected">Selected people</option>
                                        <option value="division">Everyone in a division</option>
                                        <option value="department">Entire department</option>
                                    </select>

                                    {{-- division picker --}}
                                    <select name="division_id" x-show="scope === 'division'" x-cloak class="input" x-bind:required="scope === 'division'">
                                        <option value="">— Select division —</option>
                                        @foreach($ownDivisions as $dv)
                                            <option value="{{ $dv->id }}">{{ $dv->code }} — {{ $dv->name }}</option>
                                        @endforeach
                                    </select>

                                    {{-- selected people picker --}}
                                    <div x-show="scope === 'selected'" x-cloak>
                                        <div class="flex flex-wrap gap-1 mb-1.5" x-show="picked.length">
                                            <template x-for="id in picked" :key="id">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[color:var(--color-primary)]/10 dark:bg-[color:var(--color-primary)]/25 text-[color:var(--color-primary)] dark:text-[color:var(--color-primary-light)] text-[11px]">
                                                    <span x-text="(people.find(p=>p.id===id)||{}).name"></span>
                                                    <button type="button" @click="toggle(id)">&times;</button>
                                                </span>
                                            </template>
                                        </div>
                                        <input type="text" x-model="search" class="input mb-1.5" placeholder="Search staff…">
                                        <div class="max-h-40 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700">
                                            <template x-for="p in filtered" :key="p.id">
                                                <label class="flex items-center gap-2 px-2.5 py-1.5 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                    <input type="checkbox" :checked="picked.includes(p.id)" @change="toggle(p.id)" class="rounded text-[color:var(--color-primary)]">
                                                    <span x-text="p.name + ' — ' + p.division"></span>
                                                </label>
                                            </template>
                                        </div>
                                        <template x-for="id in picked" :key="'h'+id"><input type="hidden" name="recipient_ids[]" :value="id"></template>
                                    </div>

                                    <input type="text" name="remarks" class="input" placeholder="Note to recipients (optional)">
                                    <x-btn type="submit" class="w-full">Distribute</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('archive', $document)
                            <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium hover:opacity-90 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                Close document (complete or archive)
                            </button>
                            <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40" x-data="{ outcome: '1' }">
                                <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                                      data-confirm="Close this document? This ends its active tracking.">
                                    @csrf
                                    <p class="text-xs text-gray-500 dark:text-gray-400">How is this document being closed?</p>
                                    <label class="flex items-start gap-2 text-sm p-2 rounded-lg border cursor-pointer" :class="outcome === '1' ? 'border-green-300 bg-green-50/60 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-600'">
                                        <input type="radio" name="completed" value="1" x-model="outcome" class="mt-0.5 text-green-600">
                                        <span><span class="font-medium">✅ Completed</span><span class="block text-xs text-gray-500 dark:text-gray-400">The task/request is fully done and resolved. Use this when the work is finished.</span></span>
                                    </label>
                                    <label class="flex items-start gap-2 text-sm p-2 rounded-lg border cursor-pointer" :class="outcome === '0' ? 'border-gray-400 bg-gray-100 dark:border-gray-500 dark:bg-gray-700/60' : 'border-gray-200 dark:border-gray-600'">
                                        <input type="radio" name="completed" value="0" x-model="outcome" class="mt-0.5">
                                        <span><span class="font-medium">🗄 Archived</span><span class="block text-xs text-gray-500 dark:text-gray-400">Closed and filed away <em>without</em> being completed — e.g. cancelled, withdrawn, a duplicate, or no longer needed.</span></span>
                                    </label>
                                    <textarea name="remarks" rows="2" class="input" placeholder="Reason / details (required)" required></textarea>
                                    <x-btn type="submit" variant="success" class="w-full"><span x-text="outcome === '1' ? 'Mark as Completed' : 'Archive document'"></span></x-btn>
                                </form>
                            </div>
                        @endcan

                        @if($document->isClosed())
                            <div class="text-center text-sm py-2 {{ $document->status === 'completed' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                @if($document->status === 'completed')
                                    ✅ <strong>Completed</strong> — the task is fully done and resolved.
                                @else
                                    🗄 <strong>Archived</strong> — closed and filed without completion.
                                @endif
                            </div>
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
                                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete document
                                </button>
                            </form>
                        @endcan
                    </div>
                </x-card>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
