<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="max-w-3xl mx-auto space-y-6"
         x-data="{ color: '{{ $settings['primary_color'] ?? '#4f46e5' }}' }">
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

                <div class="mt-4">
                    <label class="label">Logo</label>
                    <div class="flex items-center gap-4">
                        @if(!empty($settings['logo_path']))
                            <img src="{{ asset('storage/'.$settings['logo_path']) }}" class="w-14 h-14 rounded-lg object-contain border border-gray-200 dark:border-gray-700 bg-white p-1">
                        @else
                            <div class="w-14 h-14 rounded-lg flex items-center justify-center text-white text-xl font-bold" style="background: var(--color-primary)">{{ substr($settings['app_short_name'] ?? 'P',0,1) }}</div>
                        @endif
                        <div>
                            <input type="file" name="logo" accept="image/*" class="text-sm">
                            @if(!empty($settings['logo_path']))
                                <label class="flex items-center gap-1 text-xs text-red-600 mt-1"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">PNG/JPG, max 2 MB.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    {{-- Favicon --}}
                    <div>
                        <label class="label">Favicon (browser tab icon)</label>
                        <div class="flex items-center gap-3">
                            @if(!empty($settings['favicon_path']))
                                <img src="{{ asset('storage/'.$settings['favicon_path']) }}" class="w-8 h-8 rounded object-contain border border-gray-200 dark:border-gray-700 bg-white p-0.5">
                            @endif
                            <div>
                                <input type="file" name="favicon" accept="image/*,.ico" class="text-sm">
                                @if(!empty($settings['favicon_path']))
                                    <label class="flex items-center gap-1 text-xs text-red-600 mt-1"><input type="checkbox" name="remove_favicon" value="1"> Remove</label>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">Square PNG/ICO, max 1 MB.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Login background --}}
                    <div>
                        <label class="label">Login background image</label>
                        <div class="flex items-center gap-3">
                            @if(!empty($settings['login_bg_path']))
                                <img src="{{ asset('storage/'.$settings['login_bg_path']) }}" class="w-14 h-10 rounded object-cover border border-gray-200 dark:border-gray-700">
                            @endif
                            <div>
                                <input type="file" name="login_bg" accept="image/*" class="text-sm">
                                @if(!empty($settings['login_bg_path']))
                                    <label class="flex items-center gap-1 text-xs text-red-600 mt-1"><input type="checkbox" name="remove_login_bg" value="1"> Remove</label>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">Wide JPG/PNG, max 4 MB. A themed gradient overlays it automatically.</p>
                            </div>
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
                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="allow_desktop_receive" value="0">
                        <input type="checkbox" name="allow_desktop_receive" value="1" class="mt-1 rounded text-[color:var(--color-primary)]"
                               @checked(($settings['allow_desktop_receive'] ?? '0') === '1')>
                        <span>
                            <span class="font-medium text-sm">Allow receiving from the desktop document page</span>
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>off</strong> (default), staff can only tap <em>Receive</em> after scanning the QR with their phone.
                                When <strong>on</strong>, the intended recipient can also receive from the desktop document page.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer border-t border-gray-100 dark:border-gray-700 pt-4">
                        <input type="hidden" name="allow_cross_department" value="0">
                        <input type="checkbox" name="allow_cross_department" value="1" class="mt-1 rounded text-[color:var(--color-primary)]"
                               @checked(($settings['allow_cross_department'] ?? '0') === '1')>
                        <span>
                            <span class="font-medium text-sm">Allow sending documents to other departments</span>
                            <span class="block text-xs text-gray-400 mt-0.5">
                                When <strong>off</strong> (default), staff can only assign/forward to people in <em>their own department</em>.
                                When <strong>on</strong>, they can pick another <strong>office → division → staff</strong> to route documents between departments.
                            </span>
                        </span>
                    </label>
                </div>
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
