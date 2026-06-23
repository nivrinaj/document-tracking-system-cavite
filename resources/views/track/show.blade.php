<x-app-layout>
    <x-slot name="header">Scanned Document</x-slot>

    <div x-data="{ panel: null }" class="max-w-xl mx-auto space-y-5">
        <x-card>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-2" style="background: var(--color-primary)">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <h1 class="text-lg font-semibold">{{ $document->title }}</h1>
                <p class="text-sm text-gray-400 font-mono">{{ $document->tracking_code }}</p>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <x-status-badge :status="$document->status" />
                    <x-priority-badge :priority="$document->priority" />
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-sm mt-5 border-t border-gray-100 dark:border-gray-700 pt-4">
                <div><dt class="text-gray-400 text-xs">Type</dt><dd>{{ $document->document_type }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Current Holder</dt><dd class="font-medium">{{ $document->currentHolder?->name ?? 'Unassigned' }}<span class="block text-[11px] text-gray-400 font-normal">{{ $document->currentHolder?->orgUnit() }}</span></dd></div>
                <div><dt class="text-gray-400 text-xs">From</dt><dd>{{ $document->creator?->name ?? '—' }}<span class="block text-[11px] text-gray-400">{{ $document->creator?->orgUnit() }}</span></dd></div>
                <div><dt class="text-gray-400 text-xs">Reference</dt><dd>{{ $document->voucher_number ?? $document->reference_no ?? '—' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Released</dt><dd>{{ $document->released_at ? $document->released_at->diffForHumans() : '—' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Last action</dt><dd>{{ $document->elapsedSinceLastAction() }} ago</dd></div>
            </dl>

            {{-- Most recent movement: who last handled it + their note --}}
            @php $last = $document->logs->first(); @endphp
            @if($last)
                <div class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700 text-left">
                    <div class="flex items-center gap-2 flex-wrap">
                        <x-badge :color="$last->actionColor()">{{ $last->actionLabel() }}</x-badge>
                        <span class="text-xs text-gray-400">{{ $last->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm mt-1">
                        <span class="text-gray-500 dark:text-gray-400">Last handled by</span>
                        <span class="font-medium">{{ $last->actor?->name ?? 'System' }}</span>
                        @if($last->actor)<span class="text-xs text-gray-400">· {{ $last->actor->orgShort() }}</span>@endif
                        @if($last->toUser)
                            <span class="text-gray-400">→</span> <span class="font-medium">{{ $last->toUser->name }}</span>
                        @endif
                    </p>
                    @if($last->remarks)
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">“{{ $last->remarks }}”</p>
                    @endif
                </div>
            @endif
        </x-card>

        {{-- Primary action --}}
        <x-card>
            <h2 class="font-semibold mb-3">What would you like to do?</h2>
            <div class="space-y-3">
                @can('acknowledge', $document)
                    <form method="POST" action="{{ route('documents.acknowledge', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                          data-confirm="Acknowledge that you have received this document?">
                        @csrf
                        <p class="text-sm text-blue-700 dark:text-blue-300">🔔 This document was <strong>distributed to you</strong>. Acknowledge that you have received it.</p>
                        <x-btn type="submit" class="w-full">✅ Acknowledge receipt</x-btn>
                    </form>
                @endcan

                @can('receive', $document)
                    @php $isClaim = $document->current_holder_id === null; @endphp
                    <form method="POST" action="{{ route('documents.receive', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                          data-confirm="{{ $isClaim ? 'Claim this document for your office? You will become its holder.' : '' }}">
                        @csrf
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            @if($isClaim)
                                📥 Transferred to <strong>your office</strong>. Claiming makes you its holder — other receivers stop seeing it as unclaimed.
                            @else
                                This document is assigned to you. Confirm you have it physically.
                            @endif
                        </p>
                        <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                        <x-btn type="submit" class="w-full">{{ $isClaim ? '📥 Claim & Receive' : '✅ Receive Document' }}</x-btn>
                    </form>
                @endcan

                @can('forward', $document)
                    <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full text-left px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium">Forward to another staff</button>
                    <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2"
                              data-confirm="Forward this document to the selected staff?">
                            @csrf
                            <select name="to_user_id" class="input" required>
                                <option value="">— Forward to —</option>
                                @foreach($users->where('id', '!=', $document->current_holder_id)->groupBy(fn($u) => $u->department?->code ?? 'No office') as $group => $gu)
                                    <optgroup label="{{ $group }}">@foreach($gu as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->division?->code ?? 'Head' }}</option>@endforeach</optgroup>
                                @endforeach
                            </select>
                            <textarea name="remarks" rows="2" class="input" placeholder="Details (required)" required></textarea>
                            <x-btn type="submit" class="w-full">Forward</x-btn>
                        </form>
                    </div>
                @endcan

                @can('pending', $document)
                    <button @click="panel = panel === 'pending' ? null : 'pending'" class="w-full text-left px-4 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium">⏸ Mark as pending</button>
                    <div x-show="panel === 'pending'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <form method="POST" action="{{ route('documents.pending', $document) }}" class="space-y-2"
                              data-confirm="Mark this document as pending? Your time will pause.">
                            @csrf
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pauses your processing time (awaiting action of the origin / someone else).</p>
                            <textarea name="remarks" rows="2" class="input" placeholder="Why is this pending? (required)" required></textarea>
                            <x-btn type="submit" class="w-full">⏸ Mark pending</x-btn>
                        </form>
                    </div>
                @endcan

                @can('resume', $document)
                    <form method="POST" action="{{ route('documents.resume', $document) }}" class="space-y-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20"
                          data-confirm="Resume work on this document?">
                        @csrf
                        <p class="text-sm text-amber-700 dark:text-amber-300">⏸ This document is <strong>pending</strong>. Resume to start your timer again.</p>
                        <textarea name="remarks" rows="2" class="input" placeholder="What changed / why resume now? (required)" required></textarea>
                        <x-btn type="submit" class="w-full">▶ Resume work</x-btn>
                    </form>
                @endcan

                @can('archive', $document)
                    <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full text-left px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium">Archive / Complete</button>
                    <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                              data-confirm="Archive/close this document? This ends its active tracking.">
                            @csrf
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="completed" value="1" class="rounded"> Mark as fully completed</label>
                            <textarea name="remarks" rows="2" class="input" placeholder="Completion details (required)" required></textarea>
                            <x-btn type="submit" variant="success" class="w-full">Archive</x-btn>
                        </form>
                    </div>
                @endcan

                <a href="{{ route('documents.show', $document) }}" class="block text-center text-sm link pt-1">View full details &amp; history →</a>
            </div>
        </x-card>
    </div>
</x-app-layout>
