<x-app-layout>
    <x-slot name="header">QR Slip Settings</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('settings.edit') }}" class="text-sm link">&larr; Back to System Settings</a>
        <p class="text-sm text-gray-500 dark:text-gray-400">Configure the printable QR tracking slip — header color, badge, which document details show, and the footer.</p>

        <form method="POST" action="{{ route('qr-slip.settings.save') }}" class="space-y-6"
              x-data="{ color: '{{ $headerColor ?: $primaryColor }}', usingDefault: {{ $headerColor ? 'false' : 'true' }} }">
            @csrf @method('PUT')

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Header &amp; badge color</h2>
                <p class="text-xs text-gray-400 mb-3">Used for the slip header background and the badge pill. Defaults to the system theme color when no override is set.</p>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['#4f46e5','#2563eb','#0891b2','#059669','#16a34a','#ca8a04','#ea580c','#dc2626','#db2777','#7c3aed','#475569','#0f766e'] as $preset)
                        <button type="button" @click="color = '{{ $preset }}'; usingDefault = false"
                                class="w-9 h-9 rounded-full border-2 transition"
                                :class="color === '{{ $preset }}' ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'"
                                style="background: {{ $preset }}"></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="color" x-model="color" @input="usingDefault = false" class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 bg-transparent">
                    <input type="text" x-model="color" @input="usingDefault = false" class="input max-w-[140px] font-mono">
                    <button type="button" @click="usingDefault = true; color = '{{ $primaryColor }}'" class="text-sm link">Use theme default</button>
                    <div class="px-4 py-2 rounded-lg text-white text-sm" :style="`background:${color}`">Header preview</div>
                </div>
                <input type="hidden" name="qr_slip_header_color" :value="usingDefault ? '' : color">
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Text</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Badge text</label>
                        <input type="text" name="qr_slip_badge_text" value="{{ old('qr_slip_badge_text', $badgeText) }}" class="input" maxlength="60" required>
                        <p class="text-xs text-gray-400 mt-1">The pill label shown above the QR code (e.g. "Document Tracking Slip").</p>
                    </div>
                    <div>
                        <label class="label">Footer text</label>
                        <input type="text" name="qr_slip_footer_text" value="{{ old('qr_slip_footer_text', $footerText) }}" class="input" maxlength="80" required>
                        <p class="text-xs text-gray-400 mt-1">The small line at the very bottom of the slip (e.g. "Powered by PICTO").</p>
                    </div>
                </div>
            </x-card>

            {{-- Toggles --}}
            <x-card padding="p-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 px-4 pt-4 pb-2">Layout</p>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 mx-4 mb-4">
                    <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">Show footer line</span>
                            <span class="block text-xs text-gray-400">Print the footer text at the bottom of the slip.</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="qr_slip_show_footer" value="0">
                            <input type="checkbox" name="qr_slip_show_footer" value="1" class="peer sr-only" @checked($showFooter)>
                            <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                            <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                    <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">Show tracking URL</span>
                            <span class="block text-xs text-gray-400">Print the public tracking link text below the QR code.</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="qr_slip_show_url" value="0">
                            <input type="checkbox" name="qr_slip_show_url" value="1" class="peer sr-only" @checked($showUrl)>
                            <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                            <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
            </x-card>

            {{-- Field toggles --}}
            <x-card padding="p-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 px-4 pt-4 pb-2">Document details shown</p>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 mx-4 mb-4">
                    @foreach($fieldToggles as $key => $label)
                        <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
                            <span class="text-sm font-medium">{{ $label }}</span>
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" class="peer sr-only" @checked($fieldValues[$key])>
                                <span class="w-11 h-6 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-[color:var(--color-primary)] transition-colors"></span>
                                <span class="absolute left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-400 px-4 pb-4">Each field still only prints when the document actually has that data (e.g. Fund only shows for Vouchers/Payroll).</p>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save QR Slip settings</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
