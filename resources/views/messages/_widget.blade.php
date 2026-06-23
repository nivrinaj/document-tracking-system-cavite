{{-- Floating Messenger-style chat widget, available on every page when messaging is on. --}}
<div x-data="chatWidget()" x-init="init()" class="print:hidden">

    {{-- Launcher bubble --}}
    <button @click="toggle()" x-show="!open" x-transition
            class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full shadow-lg text-white grid place-items-center hover:brightness-110 active:scale-95 transition"
            style="background: var(--color-primary)" title="Messages">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5C3.5 15.3 3 13.7 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span id="msgBubbleBadge" class="absolute -top-1 -right-1 text-[10px] leading-none rounded-full px-1.5 py-1 bg-red-500 text-white hidden"></span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition
         class="fixed z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col
                inset-x-0 bottom-0 top-16 rounded-t-2xl
                sm:inset-x-auto sm:top-auto sm:bottom-5 sm:right-5 sm:w-[22rem] sm:h-[32rem] sm:rounded-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-2 px-3 py-2.5 text-white shrink-0" style="background: var(--color-primary)">
            <button x-show="view === 'thread'" @click="backToList()" class="p-1 -ml-1 hover:bg-white/20 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="font-semibold text-sm truncate flex-1" x-text="view === 'thread' ? title : (view === 'new' ? 'New message' : 'Messages')"></span>
            <button x-show="view === 'list'" @click="view = 'new'; loadPeople()" class="p-1 hover:bg-white/20 rounded" title="New chat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
            <a href="{{ route('messages.index') }}" class="p-1 hover:bg-white/20 rounded" title="Open full page">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            </a>
            <button @click="toggle()" class="p-1 hover:bg-white/20 rounded"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>

        {{-- LIST --}}
        <div x-show="view === 'list'" class="flex-1 overflow-y-auto">
            <template x-if="!conversations.length">
                <p class="px-4 py-10 text-center text-sm text-gray-400">No conversations yet. Tap ＋ to start one.</p>
            </template>
            <template x-for="c in conversations" :key="c.id">
                <button @click="openConversation(c.id, c.title)" class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 border-b border-gray-50 dark:border-gray-700/50">
                    <template x-if="c.avatar"><img :src="c.avatar" class="w-9 h-9 rounded-full shrink-0"></template>
                    <template x-if="!c.avatar"><span class="w-9 h-9 rounded-full grid place-items-center bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] shrink-0 text-sm font-semibold" x-text="c.title.charAt(0)"></span></template>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="font-medium text-sm truncate" x-text="c.title"></span>
                            <span class="text-[10px] text-gray-400 shrink-0" x-text="c.ago"></span>
                        </span>
                        <span class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-400 truncate" :class="c.unread ? 'font-semibold text-gray-600 dark:text-gray-200' : ''" x-text="c.last"></span>
                            <span x-show="c.unread" class="shrink-0 text-[10px] text-white rounded-full px-1.5 py-0.5" style="background: var(--color-primary)" x-text="c.unread"></span>
                        </span>
                    </span>
                </button>
            </template>
        </div>

        {{-- NEW CHAT --}}
        <div x-show="view === 'new'" class="flex-1 overflow-y-auto p-3">
            <div class="flex gap-2 mb-2" x-show="canDiv || canDept">
                <button x-show="canDiv" @click="startGroup('division')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] text-xs font-medium hover:opacity-90">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                    My Division
                </button>
                <button x-show="canDept" @click="startGroup('department')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg bg-[color:var(--color-primary)]/10 text-[color:var(--color-primary)] text-xs font-medium hover:opacity-90">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h2m-2 4h2m6-4h2m-2 4h2"/></svg>
                    My Department
                </button>
            </div>
            <input type="text" x-model="search" class="input mb-2" placeholder="Search colleague…">
            <template x-for="p in filteredPeople" :key="p.id">
                <button @click="startWith(p.id)" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <img :src="p.avatar" class="w-7 h-7 rounded-full shrink-0">
                    <span class="min-w-0"><span class="block truncate" x-text="p.name"></span><span class="block text-[11px] text-gray-400 truncate" x-text="p.office"></span></span>
                </button>
            </template>
            <p x-show="!filteredPeople.length" class="text-center text-sm text-gray-400 py-6">No colleagues match.</p>
        </div>

        {{-- THREAD --}}
        <div x-show="view === 'thread'" class="flex-1 flex flex-col min-h-0">
            <div x-ref="scroll" class="flex-1 overflow-y-auto p-3 space-y-1.5 bg-gray-50 dark:bg-gray-900/30">
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.mine ? 'text-right' : 'text-left'">
                        <div class="inline-block text-left max-w-[80%] px-3 py-2 rounded-2xl text-sm leading-snug whitespace-pre-wrap break-words shadow-sm"
                             :class="m.mine ? 'text-white rounded-br-md' : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-bl-md'"
                             :style="m.mine ? 'background: var(--color-primary)' : ''">
                            <span x-show="group && !m.mine" class="block text-[11px] font-semibold opacity-60 mb-0.5" x-text="m.sender"></span>
                            <span x-text="m.body"></span>
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5 px-1" x-text="m.time"></div>
                    </div>
                </template>
            </div>
            <form @submit.prevent="send()" class="flex items-end gap-2 p-2 border-t border-gray-100 dark:border-gray-700 shrink-0">
                <textarea x-model="body" rows="1" @keydown.enter.prevent="send()" class="input resize-none max-h-24 text-sm" placeholder="Type a message…"></textarea>
                <button type="submit" class="shrink-0 w-9 h-9 grid place-items-center rounded-full text-white" style="background: var(--color-primary)" :disabled="!body.trim()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function chatWidget() {
        return {
            open: false, view: 'list',
            conversations: [], people: [], search: '', canDiv: false, canDept: false,
            activeId: null, title: '', group: false, messages: [], body: '', lastId: 0,
            timer: null, idle: 0,
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            base: '{{ url('messages') }}',
            init() {
                // Keep the bubble badge in sync with the global unread poller.
                window.addEventListener('msg-unread', () => {});
            },
            toggle() {
                this.open = !this.open;
                if (this.open) { this.view = 'list'; this.loadConversations(); }
                else { clearTimeout(this.timer); }
            },
            async loadConversations() {
                const r = await fetch(`${this.base}/conversations`, { headers: { 'Accept': 'application/json' } });
                if (r.ok) this.conversations = (await r.json()).conversations;
            },
            async loadPeople() {
                const r = await fetch(`${this.base}/people`, { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    const d = await r.json();
                    this.people = d.people;
                    this.canDiv = d.canDivisionGroup; this.canDept = d.canDepartmentGroup;
                }
            },
            async startGroup(scope) {
                const r = await fetch(`${this.base}/group`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ scope })
                });
                if (!r.ok) return;
                const d = await r.json();
                if (d.id) { this.search = ''; this.openConversation(d.id, ''); this.loadConversations(); }
            },
            get filteredPeople() {
                const q = this.search.toLowerCase().trim();
                return this.people.filter(p => !q || p.name.toLowerCase().includes(q));
            },
            async startWith(userId) {
                const r = await fetch(`${this.base}/start`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                if (!r.ok) return;
                const d = await r.json();
                this.search = '';
                this.openConversation(d.id, '');
            },
            async openConversation(id, title) {
                this.activeId = id; this.title = title; this.view = 'thread'; this.messages = [];
                const r = await fetch(`${this.base}/${id}`, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) { this.view = 'list'; return; }
                const d = await r.json();
                this.title = d.title; this.group = d.group; this.messages = d.messages;
                this.lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                this.$nextTick(() => this.scrollBottom());
                this.schedule(2000);
                if (window.__refreshMsgBadge) window.__refreshMsgBadge();
            },
            backToList() { this.view = 'list'; this.activeId = null; clearTimeout(this.timer); this.loadConversations(); if (window.__refreshMsgBadge) window.__refreshMsgBadge(); },
            // Adaptive polling: 2s right after activity, easing to 6s when idle.
            schedule(delay) { clearTimeout(this.timer); this.timer = setTimeout(() => this.tick(), delay); },
            async tick() {
                if (this.activeId && this.open && !document.hidden) {
                    try {
                        const r = await fetch(`${this.base}/${this.activeId}/poll?after=${this.lastId}`, { headers: { 'Accept': 'application/json' } });
                        if (r.ok) {
                            const d = await r.json();
                            if (d.messages.length) {
                                const near = this.isNearBottom();
                                this.messages.push(...d.messages);
                                this.lastId = d.messages[d.messages.length - 1].id;
                                if (near) this.$nextTick(() => this.scrollBottom());
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
                const r = await fetch(`${this.base}/${this.activeId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ body: text })
                });
                if (!r.ok) return;
                const d = await r.json();
                this.messages.push(d.message); this.lastId = d.message.id;
                this.idle = 0; this.schedule(2000);
                this.$nextTick(() => this.scrollBottom());
            },
            isNearBottom() { const el = this.$refs.scroll; return el ? (el.scrollHeight - el.scrollTop - el.clientHeight < 80) : true; },
            scrollBottom() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
        };
    }
</script>
@endpush
