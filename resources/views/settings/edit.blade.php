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

            {{-- Workflow --}}
            <x-card title="Workflow">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="allow_desktop_receive" value="0">
                    <input type="checkbox" name="allow_desktop_receive" value="1" class="mt-1 rounded text-[color:var(--color-primary)]"
                           @checked(($settings['allow_desktop_receive'] ?? '0') === '1')>
                    <span>
                        <span class="font-medium text-sm">Allow receiving from the desktop document page</span>
                        <span class="block text-xs text-gray-400 mt-0.5">
                            When <strong>off</strong> (default), staff can only tap <em>Receive</em> after scanning the QR code with their phone —
                            this keeps the physical document and the scan together. When <strong>on</strong>, the intended recipient can also
                            receive directly from the document page on a desktop. (Forwarding and archiving are unaffected.)
                        </span>
                    </span>
                </label>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save Settings</x-btn>
            </div>
        </form>

        <x-card title="Future: multi-department">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                This instance is configured for a single department. Each division above can later run its own copy,
                and the system is structured so departments can be interconnected in the future. See the
                <a href="{{ route('documentation.index') }}" class="link">Documentation</a> for the roadmap and how to extend the system.
            </p>
        </x-card>
    </div>
</x-app-layout>
