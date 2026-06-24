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
                <div><dt class="text-gray-400 text-xs">Origin (encoded by)</dt><dd>{{ $document->creator?->name ?? '—' }}<span class="block text-[11px] text-gray-400">{{ $document->creator?->orgUnit() }}</span></dd></div>
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

        {{-- Digital Copy --}}
        @if(\App\Models\Document::digitalCopyEnabled())
            @php $dc = $document->digitalCopy; $canDigital = ! $document->isClosed() && auth()->id() === $document->created_by; @endphp
            <x-card>
                <h2 class="font-semibold mb-3">Digital Copy <span class="text-gray-400 font-normal text-sm">(the document's digitized original)</span></h2>
                @if($dc)
                    <a href="{{ route('attachments.download', $dc) }}" target="_blank" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="min-w-0"><span class="block text-sm font-medium">View digital copy</span><span class="block text-[11px] text-gray-400">{{ $dc->humanSize() }}</span></span>
                    </a>
                @endif
                @if($canDigital)
                    <form method="POST" action="{{ route('attachments.digitalCopy', $document) }}" enctype="multipart/form-data" class="space-y-2 {{ $dc ? 'mt-3' : '' }}" x-data="{ mode: 'camera' }">
                        @csrf
                        <div class="flex gap-1 text-xs">
                            <button type="button" @click="mode='camera'" :class="mode==='camera' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-3 py-1.5 rounded-lg font-medium">📷 Capture</button>
                            <button type="button" @click="mode='pdf'" :class="mode==='pdf' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-3 py-1.5 rounded-lg font-medium">Browse PDF File</button>
                        </div>
                        <div x-show="mode==='camera'"><x-file-drop name="images[]" accept="image/*" :multiple="true" :capture="true" icon="camera" label="Capture the document" /></div>
                        <div x-show="mode==='pdf'" x-cloak><x-file-drop name="pdf" accept="application/pdf" label="Browse a PDF (max 2 MB)" /></div>
                        <x-btn type="submit" class="w-full">{{ $dc ? 'Replace digital copy' : 'Upload digital copy' }}</x-btn>
                    </form>
                @elseif(! $dc)
                    <p class="text-sm text-gray-400">No digital copy uploaded.</p>
                @endif
            </x-card>
        @endif

        {{-- Supporting Documents --}}
        @if(\App\Models\Document::attachmentsEnabled())
            @php $canAttach = ! $document->isClosed() && (
                ($document->status === 'draft' && auth()->id() === $document->created_by)
                || ($document->status === 'received' && auth()->id() === $document->current_holder_id)
            ); @endphp
            <x-card>
                <h2 class="font-semibold mb-3">Supporting Documents <span class="text-gray-400 font-normal text-sm">({{ $document->supportingDocuments->count() }})</span></h2>
                @forelse($document->supportingDocuments as $att)
                    <div class="flex items-center gap-3 py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <span class="shrink-0 w-9 h-9 rounded-lg grid place-items-center {{ $att->hasFile() ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                        <span class="min-w-0 flex-1"><span class="block font-medium text-sm truncate">{{ $att->title }}@if($att->hasFile())<a href="{{ route('attachments.download', $att) }}" target="_blank" class="text-[11px] link ml-1">view</a>@else<span class="text-[11px] text-gray-400 ml-1">(no file)</span>@endif</span></span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No supporting documents yet.</p>
                @endforelse

                @if($canAttach)
                    <div x-data="{ mode: 'none' }" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <form method="POST" action="{{ route('attachments.store', $document) }}" enctype="multipart/form-data" class="space-y-2">
                            @csrf
                            <input type="text" name="title" class="input" placeholder="Supporting document title (required)" required maxlength="150">
                            <div class="flex gap-1 text-xs">
                                <button type="button" @click="mode='none'" :class="mode==='none' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">Title only</button>
                                <button type="button" @click="mode='camera'" :class="mode==='camera' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">📷 Capture</button>
                                <button type="button" @click="mode='pdf'" :class="mode==='pdf' ? 'bg-[color:var(--color-primary)] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="flex-1 px-2 py-1.5 rounded-lg font-medium">PDF</button>
                            </div>
                            <div x-show="mode==='camera'"><x-file-drop name="images[]" accept="image/*" :multiple="true" :capture="true" icon="camera" label="Capture page(s) — cover first" /></div>
                            <div x-show="mode==='pdf'" x-cloak><x-file-drop name="pdf" accept="application/pdf" label="Browse a PDF (max 2 MB)" /></div>
                            <x-btn type="submit" class="w-full">Add supporting document</x-btn>
                        </form>
                    </div>
                @endif
            </x-card>
        @endif

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
                    @php
                        $isClaim = $document->current_holder_id === null;
                        $latestLog = $document->logs->first();
                        $isReturned = $latestLog && $latestLog->action === 'rejected';
                        $hasAttR = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty() && ! $isReturned;
                        $reqR = $hasAttR ? $document->supportingDocuments->count() + 1 : 0;
                    @endphp
                    <div class="p-3 rounded-lg {{ $isReturned ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20' }} space-y-2" x-data="{ present: [] }">
                        @if($isReturned)
                            <div class="text-sm text-red-700 dark:text-red-300">
                                ✗ This document was <strong>rejected and returned to you</strong>@if($latestLog->actor) by {{ $latestLog->actor->name }}@endif.
                                @if($latestLog->remarks)<span class="block mt-0.5">Reason: “{{ $latestLog->remarks }}”</span>@endif
                                <span class="block mt-1 text-xs text-red-600 dark:text-red-400">Receiving it back acknowledges the rejection so you can handle the missing/incorrect item internally.</span>
                            </div>
                        @else
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                @if($isClaim)
                                    📥 Transferred to <strong>your office</strong>. Claiming makes you its holder.
                                @elseif($hasAttR)
                                    Physically check each item, then tick it. Accept only if everything is present — otherwise reject.
                                @else
                                    This document is assigned to you. Confirm you have it physically.
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
                            <x-btn type="submit" class="w-full" x-bind:disabled="present.length < {{ $reqR }}" ::class="present.length < {{ $reqR }} ? 'opacity-50 pointer-events-none' : ''">{{ $isClaim ? '📥 Claim & Receive' : ($isReturned ? '✅ Receive back (acknowledge rejection)' : '✅ Accept & Receive') }}</x-btn>
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
                @endcan

                @can('forward', $document)
                    <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full text-left px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium">Forward to another staff</button>
                    <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        @php $hasAttF = \App\Models\Document::attachmentsEnabled() && $document->supportingDocuments->isNotEmpty(); $reqF = $hasAttF ? $document->supportingDocuments->count() + 1 : 0; @endphp
                        <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2" x-data="{ present: [] }"
                              data-confirm="Forward this document to the selected staff?">
                            @csrf
                            <x-search-select name="to_user_id" placeholder="— Forward to —"
                                :options="$users->where('id', '!=', $document->current_holder_id)->map(fn($u) => ['value' => $u->id, 'label' => $u->name.' — '.($u->division?->code ?? 'Head'), 'group' => $u->department?->code ?? ''])->values()" />
                            @if($hasAttF)
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Confirm each item is physically attached:</p>
                                @include('documents._checklist')
                            @endif
                            <textarea name="remarks" rows="2" class="input" placeholder="Details (required)" required></textarea>
                            <x-btn type="submit" class="w-full" x-bind:disabled="present.length < {{ $reqF }}" ::class="present.length < {{ $reqF }} ? 'opacity-50 pointer-events-none' : ''">Forward</x-btn>
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
                    <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full text-left px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium">Close document (complete or archive)</button>
                    <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40" x-data="{ outcome: '1' }">
                        <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                              data-confirm="Close this document? This ends its active tracking.">
                            @csrf
                            <p class="text-xs text-gray-500 dark:text-gray-400">How is this document being closed?</p>
                            <label class="flex items-start gap-2 text-sm p-2 rounded-lg border" :class="outcome === '1' ? 'border-green-300 bg-green-50/60 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-600'">
                                <input type="radio" name="completed" value="1" x-model="outcome" class="mt-0.5 text-green-600">
                                <span><span class="font-medium">✅ Completed</span><span class="block text-xs text-gray-500 dark:text-gray-400">The task/request is fully done and resolved.</span></span>
                            </label>
                            <label class="flex items-start gap-2 text-sm p-2 rounded-lg border" :class="outcome === '0' ? 'border-gray-400 bg-gray-100 dark:border-gray-500 dark:bg-gray-700/60' : 'border-gray-200 dark:border-gray-600'">
                                <input type="radio" name="completed" value="0" x-model="outcome" class="mt-0.5">
                                <span><span class="font-medium">🗄 Archived</span><span class="block text-xs text-gray-500 dark:text-gray-400">Closed without completion (cancelled, duplicate, no longer needed).</span></span>
                            </label>
                            <textarea name="remarks" rows="2" class="input" placeholder="Reason / details (required)" required></textarea>
                            <x-btn type="submit" variant="success" class="w-full"><span x-text="outcome === '1' ? 'Mark as Completed' : 'Archive document'"></span></x-btn>
                        </form>
                    </div>
                @endcan

                <a href="{{ route('documents.show', $document) }}" class="block text-center text-sm link pt-1">View full details &amp; history →</a>
            </div>
        </x-card>
    </div>
</x-app-layout>
