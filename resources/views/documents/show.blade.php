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
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div><dt class="text-gray-400 text-xs uppercase">Type</dt><dd>{{ $document->document_type }}</dd></div>
                        @if($document->voucher_number)
                            <div><dt class="text-gray-400 text-xs uppercase">Voucher No.</dt><dd class="font-mono">{{ $document->voucher_number }}</dd></div>
                        @endif
                        <div><dt class="text-gray-400 text-xs uppercase">Reference No.</dt><dd>{{ $document->reference_no ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Source / Origin</dt><dd>{{ $document->source ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Division</dt><dd>{{ $document->division?->name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Encoded by</dt><dd>{{ $document->creator?->name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Current Holder</dt><dd class="font-medium">{{ $document->currentHolder?->name ?? 'Unassigned' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Received at dept</dt><dd>{{ $document->received_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400 text-xs uppercase">Released</dt><dd>{{ $document->released_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                        <div>
                            <dt class="text-gray-400 text-xs uppercase">Age</dt>
                            <dd>{{ $document->age() }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 text-xs uppercase">{{ $document->isClosed() ? 'Turnaround' : 'Idle / since last action' }}</dt>
                            <dd>
                                @if($document->isClosed())
                                    {{ $document->turnaround() ?? '—' }}
                                @else
                                    <x-badge :color="$document->agingColor()">{{ $document->elapsedSinceLastAction() }}</x-badge>
                                @endif
                            </dd>
                        </div>
                        @if($document->description)
                            <div class="sm:col-span-2"><dt class="text-gray-400 text-xs uppercase">Description</dt><dd>{{ $document->description }}</dd></div>
                        @endif
                    </dl>
                </x-card>

                {{-- Concerned staff --}}
                <x-card title="Concerned staff (can track this document)">
                    <div class="flex flex-wrap gap-2">
                        @foreach($document->assignees as $person)
                            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-xs">
                                <img src="{{ $person->avatar_url }}" class="w-5 h-5 rounded-full"> {{ $person->name }}
                            </span>
                        @endforeach
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
                                    <span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span>
                                    @if($log->toUser) → <span class="font-medium">{{ $log->toUser->name }}</span> @endif
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

                {{-- Action panel --}}
                <x-card title="Actions">
                    <div class="space-y-3">

                        @can('assign', $document)
                            <button @click="panel = panel === 'assign' ? null : 'assign'" class="w-full text-left px-4 py-2 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium hover:opacity-90">Assign / Re-assign</button>
                            <div x-show="panel === 'assign'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.assign', $document) }}" class="space-y-2"
                                      onsubmit="return confirm('Assign / re-assign this document to the selected staff?')">
                                    @csrf
                                    <select name="assignee_id" class="input" required>
                                        <option value="">— Select staff —</option>
                                        @foreach($users->groupBy(fn($u) => $u->division?->code ?? 'No division') as $group => $gu)
                                            <optgroup label="{{ $group }}">
                                                @foreach($gu as $u)<option value="{{ $u->id }}" @selected($document->current_holder_id==$u->id)>{{ $u->name }}</option>@endforeach
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
                                  onsubmit="return confirm('Release this document to {{ $document->currentHolder?->name }}? You will then print and attach the QR.')">
                                @csrf
                                <p class="text-xs text-amber-700 dark:text-amber-300">Releasing hands the document to <strong>{{ $document->currentHolder?->name }}</strong>. Print and attach the QR.</p>
                                <input type="text" name="remarks" class="input" placeholder="Release remarks (optional)">
                                <x-btn type="submit" variant="primary" class="w-full">🚀 Release Document</x-btn>
                            </form>
                        @endcan

                        @can('receive', $document)
                            @if(($settings['allow_desktop_receive'] ?? '0') === '1')
                                <form method="POST" action="{{ route('documents.receive', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    @csrf
                                    <p class="text-xs text-blue-700 dark:text-blue-300">Confirm you physically received this document.</p>
                                    <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                                    <x-btn type="submit" variant="primary" class="w-full">✅ Receive Document</x-btn>
                                </form>
                            @else
                                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-center">
                                    <p class="text-xs text-blue-700 dark:text-blue-300">This document is assigned to you. 📱 <strong>Scan the QR code with your phone</strong> to receive it.</p>
                                </div>
                            @endif
                        @endcan

                        @can('forward', $document)
                            <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full text-left px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:opacity-90">Forward to another staff</button>
                            <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2"
                                      onsubmit="return confirm('Forward this document to the selected staff?')">
                                    @csrf
                                    <select name="to_user_id" class="input" required>
                                        <option value="">— Forward to —</option>
                                        @foreach($users->groupBy(fn($u) => $u->division?->code ?? 'No division') as $group => $gu)
                                            <optgroup label="{{ $group }}">
                                                @foreach($gu as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <textarea name="remarks" rows="2" class="input" placeholder="Details about this action (required)" required></textarea>
                                    <x-btn type="submit" class="w-full">Forward</x-btn>
                                </form>
                            </div>
                        @endcan

                        @can('archive', $document)
                            <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full text-left px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium hover:opacity-90">Archive / Complete</button>
                            <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                                      onsubmit="return confirm('Archive/close this document? This ends its active tracking.')">
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
                        @endif

                        @can('delete', $document)
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Delete this document permanently?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full text-center px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm">Delete document</button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
