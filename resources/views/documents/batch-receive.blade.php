<x-app-layout>
    <x-slot name="header">Batch Receive</x-slot>

    <div class="max-w-2xl mx-auto space-y-4"
         x-data="{
            scan: '',
            msg: '', msgOk: true,
            selected: [],
            docs: @js($docs->map(fn ($d) => ['id' => $d->id, 'code' => strtoupper($d->tracking_code)])),
            addScan() {
                let code = this.scan.trim().toUpperCase();
                if (!code) return;
                // Accept a full scanned URL (…/track/PGC-2026-XXXX) or just the code.
                const hit = this.docs.find(d => code === d.code || code.endsWith('/' + d.code) || code.includes(d.code));
                if (hit) {
                    if (!this.selected.includes(hit.id)) this.selected.push(hit.id);
                    this.msg = '✓ Added ' + hit.code; this.msgOk = true;
                } else {
                    this.msg = '✗ Not in your receivable list: ' + code; this.msgOk = false;
                }
                this.scan = '';
            },
            get count() { return this.selected.length; },
            allIds() { return this.docs.map(d => d.id); },
            selectAll() { this.selected = this.allIds(); },
            clearAll() { this.selected = []; },
         }">

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">Receive a stack of documents at once.</p>
            <a href="{{ route('dashboard') }}" class="text-sm link">← Back</a>
        </div>

        {{-- Scan box --}}
        <x-card>
            <label class="label">Scan or type tracking code</label>
            <input type="text" x-model="scan" @keydown.enter.prevent="addScan()" autofocus
                   class="input font-mono" placeholder="Scan a QR with a handheld scanner, or type a code then press Enter">
            <p class="text-xs mt-2" :class="msgOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="msg"></p>
            <p class="text-xs text-gray-400 mt-1">
                Tip: a USB/Bluetooth QR scanner types the code and presses Enter automatically — just scan one after another, then press <strong>Receive selected</strong>. On a phone you can simply tick the documents below.
            </p>
        </x-card>

        <form method="POST" action="{{ route('documents.batchReceive.store') }}">
            @csrf
            <x-card padding="p-0">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-sm">Receivable documents <span class="text-gray-400 font-normal">({{ $docs->count() }})</span></h2>
                    <div class="flex items-center gap-3 text-xs">
                        <button type="button" @click="selectAll()" class="link">Select all</button>
                        <button type="button" @click="clearAll()" class="text-gray-400 hover:text-gray-600">Clear</button>
                    </div>
                </div>

                @forelse($docs as $doc)
                    <label class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 dark:border-gray-700/50 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <input type="checkbox" name="document_ids[]" value="{{ $doc->id }}" x-model.number="selected"
                               class="rounded text-[color:var(--color-primary)]">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-sm truncate">{{ $doc->title }}</div>
                            <div class="text-xs text-gray-400 truncate">
                                <span class="font-mono">{{ $doc->tracking_code }}</span> · from {{ $doc->creator?->name ?? '—' }}
                                @if($doc->department) · {{ $doc->department->code }} @endif
                            </div>
                        </div>
                        <x-badge :color="$doc->receive_kind === 'claim' ? 'amber' : 'blue'">{{ $doc->receive_kind === 'claim' ? 'Claim' : 'Receive' }}</x-badge>
                    </label>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-gray-400">Nothing is waiting for you to receive right now. 🎉</p>
                @endforelse
            </x-card>

            @if($docs->isNotEmpty())
                <div class="flex items-center justify-end gap-3 mt-4">
                    <span class="text-sm text-gray-500" x-show="count > 0"><span x-text="count"></span> selected</span>
                    <x-btn type="submit" x-bind:disabled="count === 0" ::class="count === 0 ? 'opacity-50 pointer-events-none' : ''">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Receive selected
                    </x-btn>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
