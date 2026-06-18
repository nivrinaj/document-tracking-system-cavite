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
                <div><dt class="text-gray-400 text-xs">Current Holder</dt><dd class="font-medium">{{ $document->currentHolder?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">From</dt><dd>{{ $document->creator?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Reference</dt><dd>{{ $document->voucher_number ?? $document->reference_no ?? '—' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Released</dt><dd>{{ $document->released_at ? $document->released_at->diffForHumans() : '—' }}</dd></div>
                <div><dt class="text-gray-400 text-xs">Last action</dt><dd>{{ $document->elapsedSinceLastAction() }} ago</dd></div>
            </dl>
        </x-card>

        {{-- Primary action --}}
        <x-card>
            <h2 class="font-semibold mb-3">What would you like to do?</h2>
            <div class="space-y-3">
                @can('receive', $document)
                    <form method="POST" action="{{ route('documents.receive', $document) }}" class="space-y-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        @csrf
                        <p class="text-sm text-blue-700 dark:text-blue-300">This document is assigned to you. Confirm you have it physically.</p>
                        <input type="text" name="remarks" class="input" placeholder="Remarks (optional)">
                        <x-btn type="submit" class="w-full">✅ Receive Document</x-btn>
                    </form>
                @endcan

                @can('forward', $document)
                    <button @click="panel = panel === 'forward' ? null : 'forward'" class="w-full text-left px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium">Forward to another staff</button>
                    <div x-show="panel === 'forward'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <form method="POST" action="{{ route('documents.forward', $document) }}" class="space-y-2"
                              onsubmit="return confirm('Forward this document to the selected staff?')">
                            @csrf
                            <select name="to_user_id" class="input" required>
                                <option value="">— Forward to —</option>
                                @foreach($users->groupBy(fn($u) => $u->division?->code ?? 'No division') as $group => $gu)
                                    <optgroup label="{{ $group }}">@foreach($gu as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</optgroup>
                                @endforeach
                            </select>
                            <textarea name="remarks" rows="2" class="input" placeholder="Details (required)" required></textarea>
                            <x-btn type="submit" class="w-full">Forward</x-btn>
                        </form>
                    </div>
                @endcan

                @can('archive', $document)
                    <button @click="panel = panel === 'archive' ? null : 'archive'" class="w-full text-left px-4 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium">Archive / Complete</button>
                    <div x-show="panel === 'archive'" x-cloak class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-2"
                              onsubmit="return confirm('Archive/close this document? This ends its active tracking.')">
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
