<x-app-layout>
    <x-slot name="header">Messages</x-slot>

    @php $me = auth()->user(); @endphp

    <div x-data="chat()" x-init="init()"
         class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-3"
         style="height: calc(100dvh - 9.5rem); min-height: 28rem;">

        {{-- ───────── Conversation list ───────── --}}
        <div class="lg:col-span-1 border-r border-gray-200 dark:border-gray-700 flex flex-col"
             :class="activeId ? 'hidden lg:flex' : 'flex'">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold">Chats</h2>
                <button @click="newChatOpen = !newChatOpen" class="inline-flex items-center gap-1 text-sm font-medium" style="color: var(--color-primary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New
                </button>
            </div>

            {{-- New chat picker --}}
            <div x-show="newChatOpen" x-cloak class="p-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                @if($canDivisionGroup || $canDepartmentGroup)
                    <div class="flex gap-2 mb-2">
                        @if($canDivisionGroup)
                            <form method="POST" action="{{ route('messages.group') }}" class="flex-1">
                                @csrf <input type="hidden" name="scope" value="division">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] text-xs font-medium hover:opacity-90">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                    My Division
                                </button>
                            </form>
                        @endif
                        @if($canDepartmentGroup)
                            <form method="POST" action="{{ route('messages.group') }}" class="flex-1">
                                @csrf <input type="hidden" name="scope" value="department">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] text-xs font-medium hover:opacity-90">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h2m-2 4h2m6-4h2m-2 4h2"/></svg>
                                    My Department
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
                <input type="text" x-model="search" class="input mb-2" placeholder="Search colleague…">
                <div class="max-h-60 overflow-y-auto space-y-1">
                    @foreach($people as $p)
                        <form method="POST" action="{{ route('messages.start') }}"
                              x-show="match('{{ strtolower($p->name) }}')">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $p->id }}">
                            <button type="submit" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm hover:bg-white dark:hover:bg-gray-700">
                                <img src="{{ $p->avatar_url }}" class="w-7 h-7 rounded-full shrink-0">
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $p->name }}</span>
                                    <span class="block text-[11px] text-gray-400 truncate">{{ $p->department?->code ?? '—' }}</span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50">
                @forelse($conversations as $c)
                    @php
                        $other = $c->otherParticipant($me);
                        $unread = $c->unreadCountFor($me);
                        $last = $c->latestMessage;
                    @endphp
                    <button @click="open({{ $c->id }})"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40"
                            :class="activeId === {{ $c->id }} ? 'bg-gray-50 dark:bg-gray-700/50' : ''">
                        @if($c->is_group)
                            <span class="w-9 h-9 rounded-full grid place-items-center bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                            </span>
                        @else
                            <img src="{{ $other?->avatar_url }}" class="w-9 h-9 rounded-full shrink-0">
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="font-medium text-sm truncate">{{ $c->titleFor($me) }}</span>
                                @if($last)<span class="text-[10px] text-gray-400 shrink-0">{{ $last->created_at->diffForHumans(null, true) }}</span>@endif
                            </span>
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-xs text-gray-400 truncate">{{ $last ? \Illuminate\Support\Str::limit($last->body, 32) : 'No messages yet' }}</span>
                                @if($unread > 0)<span class="shrink-0 text-[10px] bg-[color:var(--color-primary)] text-white rounded-full px-1.5 py-0.5">{{ $unread }}</span>@endif
                            </span>
                        </span>
                    </button>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-gray-400">No conversations yet. Tap <strong>New</strong> to start one.</p>
                @endforelse
            </div>
        </div>

        {{-- ───────── Active conversation ───────── --}}
        <div class="lg:col-span-2 flex flex-col" :class="activeId ? 'flex' : 'hidden lg:flex'">
            <template x-if="!activeId">
                <div class="flex-1 grid place-items-center text-center p-8">
                    <div>
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5C3.5 15.3 3 13.7 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <p class="text-sm text-gray-400">Select a chat or start a new one to begin messaging.</p>
                    </div>
                </div>
            </template>

            <template x-if="activeId">
                <div class="flex flex-col h-full">
                    {{-- header --}}
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <button @click="close()" class="lg:hidden p-1 -ml-1 text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                        <h2 class="font-semibold text-sm" x-text="title"></h2>
                    </div>

                    {{-- messages --}}
                    <div x-ref="scroll" class="flex-1 overflow-y-auto px-4 py-4 space-y-1.5 bg-gray-50 dark:bg-gray-900/30">
                        <template x-for="m in messages" :key="m.id">
                            <div :class="m.mine ? 'text-right' : 'text-left'">
                                <div class="inline-block text-left max-w-[75%] px-3 py-2 rounded-2xl text-sm leading-snug whitespace-pre-wrap break-words shadow-sm"
                                     :class="m.mine ? 'text-white rounded-br-md' : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-bl-md'"
                                     :style="m.mine ? 'background: var(--color-primary)' : ''">
                                    <span x-show="group && !m.mine" class="block text-[11px] font-semibold opacity-60 mb-0.5" x-text="m.sender"></span>
                                    <span x-text="m.body"></span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5 px-1" x-text="m.time"></div>
                            </div>
                        </template>
                    </div>

                    {{-- composer --}}
                    <form @submit.prevent="send()" class="flex items-end gap-2 p-3 border-t border-gray-100 dark:border-gray-700">
                        <textarea x-model="body" rows="1" @keydown.enter.prevent="send()"
                                  class="input resize-none max-h-32" placeholder="Type a message… (Enter to send)"></textarea>
                        <button type="submit" class="shrink-0 w-10 h-10 grid place-items-center rounded-full text-white" style="background: var(--color-primary)" :disabled="!body.trim()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>
            </template>
        </div>
    </div>

    @push('scripts')
    <script>
        function chat() {
            return {
                activeId: @js($openId),
                title: '', group: false, messages: [], body: '', lastId: 0,
                timer: null, idle: 0,
                newChatOpen: false, search: '',
                csrf: document.querySelector('meta[name="csrf-token"]').content,
                match(name) { const q = this.search.toLowerCase().trim(); return !q || name.includes(q); },
                init() { if (this.activeId) this.open(this.activeId); },
                async open(id) {
                    this.activeId = id;
                    const res = await fetch(`{{ url('messages') }}/${id}`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) { this.activeId = null; return; }
                    const data = await res.json();
                    this.title = data.title; this.group = data.group; this.messages = data.messages;
                    this.lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                    this.$nextTick(() => this.scrollBottom());
                    this.schedule(2000); // start fast
                    if (window.__refreshMsgBadge) window.__refreshMsgBadge();
                },
                close() { this.activeId = null; clearTimeout(this.timer); },
                // Adaptive polling: fast (2s) right after activity, easing to 6s when idle.
                schedule(delay) { clearTimeout(this.timer); this.timer = setTimeout(() => this.tick(), delay); },
                async tick() {
                    if (this.activeId && !document.hidden) {
                        try {
                            const res = await fetch(`{{ url('messages') }}/${this.activeId}/poll?after=${this.lastId}`, { headers: { 'Accept': 'application/json' } });
                            if (res.ok) {
                                const data = await res.json();
                                if (data.messages.length) {
                                    const nearBottom = this.isNearBottom();
                                    this.messages.push(...data.messages);
                                    this.lastId = data.messages[data.messages.length - 1].id;
                                    if (nearBottom) this.$nextTick(() => this.scrollBottom());
                                    if (window.__refreshMsgBadge) window.__refreshMsgBadge();
                                    this.idle = 0;
                                } else { this.idle++; }
                            }
                        } catch (e) { /* ignore */ }
                    }
                    this.schedule(Math.min(6000, 2000 + this.idle * 1000));
                },
                async send() {
                    const text = this.body.trim();
                    if (!text || !this.activeId) return;
                    this.body = '';
                    const res = await fetch(`{{ url('messages') }}/${this.activeId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ body: text })
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.messages.push(data.message); this.lastId = data.message.id;
                    this.idle = 0; this.schedule(2000); // keep it snappy after sending
                    this.$nextTick(() => this.scrollBottom());
                },
                isNearBottom() { const el = this.$refs.scroll; return el ? (el.scrollHeight - el.scrollTop - el.clientHeight < 80) : true; },
                scrollBottom() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
            };
        }
    </script>
    @endpush
</x-app-layout>
