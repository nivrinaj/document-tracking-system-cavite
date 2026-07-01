<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="max-w-3xl mx-auto space-y-6"
         x-data="{ color: '{{ $settings['primary_color'] ?? '#4f46e5' }}' }">
        <div class="flex justify-end">
            <a href="{{ route('qr-slip.settings') }}" class="inline-flex items-center gap-1.5 text-sm link">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 12a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zM14 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm3 12h2m-5 0h.01M14 19h2m-2-3v3m5-3v3"/></svg>
                QR Slip settings
            </a>
        </div>
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Branding --}}
            <x-card title="Branding">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Application Name</label>
                        <input type="text" name="app_name" value="{{ $settings['app_name'] }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Short Name (sidebar)</label>
                        <input type="text" name="app_short_name" value="{{ $settings['app_short_name'] }}" class="input">
                    </div>
                    <div>
                        <label class="label">Organization</label>
                        <input type="text" name="organization" value="{{ $settings['organization'] }}" class="input">
                    </div>
                    <div>
                        <label class="label">Footer Text</label>
                        <input type="text" name="footer_text" value="{{ $settings['footer_text'] }}" class="input">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5">
                    {{-- Logo --}}
                    <div>
                        <label class="label">Logo</label>
                        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4 flex flex-col items-center gap-3">
                            @if(!empty($settings['logo_path']))
                                <img src="{{ asset('storage/'.$settings['logo_path']) }}" class="w-16 h-16 rounded-xl object-contain border border-gray-200 dark:border-gray-700 bg-white p-1.5 shadow-sm">
                            @else
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-sm" style="background: var(--color-primary)">{{ substr($settings['app_short_name'] ?? 'P',0,1) }}</div>
                            @endif
                            <x-file-drop name="logo" accept="image/*" label="Upload logo" />
                            @if(!empty($settings['logo_path']))
                                <label class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 cursor-pointer hover:underline">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded"> Remove current logo
                                </label>
                            @endif
                            <p class="text-[11px] text-gray-400 text-center">PNG/JPG, max 2 MB.</p>
                        </div>
                    </div>

                    {{-- Favicon --}}
                    <div>
                        <label class="label">Favicon</label>
                        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4 flex flex-col items-center gap-3">
                            @if(!empty($settings['favicon_path']))
                                <img src="{{ asset('storage/'.$settings['favicon_path']) }}" class="w-16 h-16 rounded-xl object-contain border border-gray-200 dark:border-gray-700 bg-white p-2 shadow-sm">
                            @else
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center bg-gray-200 dark:bg-gray-700 text-gray-400">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                            @endif
                            <x-file-drop name="favicon" accept="image/*,.ico" label="Upload favicon" />
                            @if(!empty($settings['favicon_path']))
                                <label class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 cursor-pointer hover:underline">
                                    <input type="checkbox" name="remove_favicon" value="1" class="rounded"> Remove current favicon
                                </label>
                            @endif
                            <p class="text-[11px] text-gray-400 text-center">Square PNG/ICO, max 1 MB.</p>
                        </div>
                    </div>

                    {{-- Login background --}}
                    <div>
                        <label class="label">Login background</label>
                        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4 flex flex-col items-center gap-3">
                            @if(!empty($settings['login_bg_path']))
                                <img src="{{ asset('storage/'.$settings['login_bg_path']) }}" class="w-full h-16 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm">
                            @else
                                <div class="w-full h-16 rounded-xl flex items-center justify-center bg-gray-200 dark:bg-gray-700 text-gray-400">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <x-file-drop name="login_bg" accept="image/*" label="Upload background" />
                            @if(!empty($settings['login_bg_path']))
                                <label class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 cursor-pointer hover:underline">
                                    <input type="checkbox" name="remove_login_bg" value="1" class="rounded"> Remove current background
                                </label>
                            @endif
                            <p class="text-[11px] text-gray-400 text-center">Wide JPG/PNG, max 4 MB. A themed gradient overlays it automatically.</p>
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Theme color --}}
            <x-card title="Theme color">
                <p class="text-xs text-gray-400 mb-3">Pick the primary color used across the whole system. (Dark mode can be toggled any time from the top bar 🌙.)</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach(['#4f46e5','#2563eb','#0891b2','#059669','#16a34a','#ca8a04','#ea580c','#dc2626','#db2777','#7c3aed','#475569','#0f766e'] as $preset)
                        <button type="button" @click="color = '{{ $preset }}'"
                                class="w-9 h-9 rounded-full border-2 transition"
                                :class="color === '{{ $preset }}' ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'"
                                style="background: {{ $preset }}"></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="color" name="primary_color" class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 bg-transparent">
                    <input type="text" x-model="color" class="input max-w-[140px] font-mono">
                    <div class="px-4 py-2 rounded-lg text-white text-sm" :style="`background:${color}`">Preview button</div>
                </div>
            </x-card>

            {{-- Operations --}}
            <x-card title="Operations">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Tracking code prefix</label>
                        <input type="text" name="tracking_prefix" value="{{ $settings['tracking_prefix'] ?? 'PGC' }}" class="input" maxlength="10" required>
                        <p class="text-xs text-gray-400 mt-1">New codes look like <span class="font-mono">{{ $settings['tracking_prefix'] ?? 'PGC' }}-{{ date('Y') }}-XXXXX</span>. (Existing codes don't change.)</p>
                    </div>
                    <div>
                        <label class="label">Records per page</label>
                        <input type="number" name="records_per_page" value="{{ $settings['records_per_page'] ?? '12' }}" class="input" min="5" max="100" required>
                        <p class="text-xs text-gray-400 mt-1">Rows shown per page in tables (5–100).</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Support contact (shown in the footer)</label>
                        <input type="text" name="support_contact" value="{{ $settings['support_contact'] ?? '' }}" class="input" placeholder="e.g. ISDA Help Desk · local 1234 · support@cavite.gov.ph">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Dashboard announcement (optional)</label>
                        <textarea name="announcement" rows="2" class="input" placeholder="Shown as a banner on everyone's dashboard. Leave blank to hide.">{{ $settings['announcement'] ?? '' }}</textarea>
                    </div>
                </div>
            </x-card>

            {{-- Workflow --}}
            <x-card title="Workflow">
                <div class="space-y-4"
                     x-data="{ deskOn: {{ ($settings['allow_desktop_receive'] ?? '0') === '1' ? 'true' : 'false' }}, deskScope: '{{ $settings['desktop_receive_scope'] ?? 'all' }}' }">
                    <x-toggle name="allow_desktop_receive" x-model="deskOn" label="Allow receiving from the desktop document page">
                        <span class="block text-xs text-gray-400 mt-0.5">
                            When <strong>off</strong> (default), staff can only tap <em>Receive</em> after scanning the QR with their phone.
                            When <strong>on</strong>, the intended recipient can also receive from the desktop document page.
                        </span>
                    </x-toggle>

                    <div x-show="deskOn" x-cloak class="ml-[3.25rem] -mt-2 space-y-3">
                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 w-fit text-sm">
                            <button type="button" @click="deskScope = 'all'" class="px-3 py-1.5 rounded-md transition-colors" :class="deskScope === 'all' ? 'bg-[color:var(--color-primary)] text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">All offices</button>
                            <button type="button" @click="deskScope = 'selected'" class="px-3 py-1.5 rounded-md transition-colors" :class="deskScope === 'selected' ? 'bg-[color:var(--color-primary)] text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">Select offices</button>
                        </div>
                        <input type="hidden" name="desktop_receive_scope" :value="deskScope">

                        <div x-show="deskScope === 'selected'" x-cloak>
                            <p class="text-xs text-gray-400 mb-1.5">Only staff in these offices get the desktop option; everyone else still scans the QR.</p>
                            <div class="max-w-md" x-data="multiSelect({
                                items: @js($departments->map(fn($d) => ['id' => (string) $d->id, 'label' => $d->code.' — '.$d->name])),
                                selected: @js(array_map('strval', array_filter(explode(',', (string) ($settings['desktop_receive_departments'] ?? ''))))),
                                name: 'desktop_receive_departments[]',
                                placeholder: '— Select offices —',
                            })">
                                <x-reports._multi-select />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="allow_cross_department" :checked="($settings['allow_cross_department'] ?? '0') === '1'" label="Allow sending documents to other departments">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>off</strong> (default), staff can only assign/forward to people in <em>their own department</em>.
                                When <strong>on</strong>, they can pick another <strong>office → division → staff</strong> to route documents between departments.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_priority" :checked="($settings['enable_priority'] ?? '0') === '1'" label="Enable the “Priority” field">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>off</strong> (default), the priority field is hidden everywhere — encode form, lists, document details and reports.
                                When <strong>on</strong>, documents can be tagged Low / Normal / High / Urgent and filtered and reported on by priority.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_route_items" :checked="($settings['enable_route_items'] ?? '0') === '1'" label="Enable “Route slip” multi-document tracking">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong>, a single document/QR (a “route slip”) can list several individual documents. The holder can mark each one
                                <strong>Cleared</strong> (good to go) or <strong>Rejected</strong> (returned to origin) — so partial outcomes (e.g. 4 cleared, 1 rejected) are tracked.
                                When <strong>off</strong> (default), this is hidden.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_batch_receive" :checked="($settings['enable_batch_receive'] ?? '1') === '1'" label="Enable “Batch receive”">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong> (default), staff can receive a whole stack of QR-tagged documents at once from the <strong>Batch receive</strong> page (scan-scan-scan, then receive).
                                When <strong>off</strong>, documents are received one at a time as usual.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_document_linking" :checked="($settings['enable_document_linking'] ?? '1') === '1'" label="Enable “Link related documents”">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong> (default), a document can be linked to other related documents (by tracking code) so their histories cross-reference.
                                When <strong>off</strong>, the Related-documents panel is hidden.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_attachments" :checked="($settings['enable_attachments'] ?? '0') === '1'" label="Enable “Supporting Documents” &amp; handover verification">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong>, the holder can list supporting documents (a <strong>title</strong> each, with an optional PDF or captured pages).
                                On hand-over the sender ticks each item as physically attached; the receiver ticks each item present to <strong>accept</strong>, or <strong>rejects</strong> (returns it to the sender) if something is missing.
                                When <strong>off</strong> (default), this is hidden.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_digital_copy" :checked="($settings['enable_digital_copy'] ?? '0') === '1'" label="Enable “Digital Copy” of the document">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong>, the <strong>encoder</strong> can upload one digital copy (PDF or image, max 2 MB) of the document they're encoding. Everyone concerned can view it; it does <em>not</em> affect receiving/rejecting (that stays tied to Supporting Documents).
                                When <strong>off</strong> (default), this is hidden.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_messaging" :checked="($settings['enable_messaging'] ?? '0') === '1'" label="Enable in-app messaging (chat)">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong>, staff get a <strong>Messages</strong> area to chat with colleagues (handy to follow up or ask about a document), with live unread badges and new-message alerts.
                                When <strong>off</strong> (default), messaging is hidden entirely.
                            </span>
                        </x-toggle>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-toggle name="enable_user_delete" :checked="($settings['enable_user_delete'] ?? '1') === '1'" label="Allow deleting user accounts">
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>on</strong> (default), Super Admins/managers can permanently delete a user from the Users page.
                                When <strong>off</strong>, the Delete option is hidden and blocked, even if requested directly — deactivate accounts with the Active toggle instead.
                            </span>
                        </x-toggle>
                    </div>

                    {{-- Messaging options (only meaningful when chat is on) --}}
                    @php $excludedRoles = json_decode($settings['messaging_excluded_roles'] ?? '[]', true) ?: []; @endphp
                    <div class="ml-7 pl-1 border-l-2 border-gray-100 dark:border-gray-700 space-y-4">
                        <div>
                            <label class="label">Who can staff chat with?</label>
                            <select name="messaging_scope" class="input max-w-sm">
                                <option value="all" @selected(($settings['messaging_scope'] ?? 'all') === 'all')>Anyone — staff from any office</option>
                                <option value="office" @selected(($settings['messaging_scope'] ?? 'all') === 'office')>Their own office only</option>
                            </select>
                        </div>
                        <div>
                            <span class="label">Exclude these roles from chat</span>
                            <p class="text-xs text-gray-400 -mt-1 mb-2">Selected roles can't use chat and won't appear as someone to message (e.g. Governor, Vice Governor, Chiefs of Staff).</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 max-h-56 overflow-y-auto pr-1">
                                @foreach($roles as $roleName)
                                    @continue($roleName === 'Super Admin')
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="messaging_excluded_roles[]" value="{{ $roleName }}"
                                               class="rounded text-[color:var(--color-primary)]" @checked(in_array($roleName, $excludedRoles))>
                                        {{ $roleName }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Deadline Highlighting (default)">
                <p class="text-xs text-gray-400 -mt-1 mb-4">Colors used for the Deadline column and row highlighting wherever an office hasn't customized its own (Departments → edit, when deadlines are enabled).</p>
                <x-deadline-rules-editor prefix="global" :rules="$deadlineRules" :overdue-color="$deadlineOverdueColor" />
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save Settings</x-btn>
            </div>
        </form>

        @role('Super Admin')
        <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900/50 rounded-xl shadow-sm">
            <div class="px-5 py-3 border-b border-red-100 dark:border-red-900/40 font-semibold text-sm text-red-600">⚠ Danger Zone</div>
            <div class="p-5 space-y-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-2xl">
                    Use these to wipe <strong>test data</strong> before entering real records. Every action is <strong>permanent and cannot be undone</strong>.
                    Deleting users keeps Super Admin accounts so you don't get locked out. Your settings &amp; document types are always kept.
                </p>

                @php
                    $dangerItems = [
                        ['documents', 'Delete all documents', 'Removes every document, its history, attachments-of-record, notifications and the activity log.'],
                        ['users', 'Delete all users', 'Removes all users AND all documents/history, leaving only Super Admin accounts.'],
                        ['divisions', 'Delete all divisions', 'Removes every division (users keep their department).'],
                        ['departments', 'Delete all departments', 'Removes every department and its divisions.'],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($dangerItems as [$target, $label, $desc])
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex flex-col">
                            <div class="text-sm font-medium">{{ $label }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 mb-3 flex-1">{{ $desc }}</div>
                            <form method="POST" action="{{ route('settings.resetData') }}"
                                  data-confirm="{{ $label }}? This is permanent and cannot be undone.">
                                @csrf
                                <input type="hidden" name="target" value="{{ $target }}">
                                <button type="submit" class="act-del border border-red-200 dark:border-red-900/50">🗑 {{ $label }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-red-100 dark:border-red-900/40 pt-3">
                    <form method="POST" action="{{ route('settings.resetData') }}"
                          data-confirm="DELETE EVERYTHING — all documents, users (except Super Admins), divisions and departments? This is permanent.">
                        @csrf
                        <input type="hidden" name="target" value="all">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Reset everything (start fresh)
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endrole
    </div>
</x-app-layout>
