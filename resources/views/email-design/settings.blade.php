<x-app-layout>
    <x-slot name="header">Email Design Settings</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('notification-settings.edit') }}" class="text-sm link">&larr; Back to Notification Settings</a>
        <p class="text-sm text-gray-500 dark:text-gray-400">Configure how notification emails look — header color, organization line, footer, and which optional sections show. Use "Preview" on the Notification Settings page to see changes with sample data.</p>

        <form method="POST" action="{{ route('email-design.settings.save') }}" class="space-y-6"
              x-data="{ color: '{{ $headerColor ?: $primaryColor }}', usingDefault: {{ $headerColor ? 'false' : 'true' }} }">
            @csrf @method('PUT')

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Header color</h2>
                <p class="text-xs text-gray-400 mb-3">Used for the email header band and the call-to-action button. Defaults to the system theme color when no override is set.</p>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['#4f46e5','#2563eb','#0891b2','#059669','#16a34a','#ca8a04','#ea580c','#dc2626','#db2777','#7c3aed','#1c3d77','#0f766e'] as $preset)
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
                <input type="hidden" name="email_header_color" :value="usingDefault ? '' : color">
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-1">Text</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Organization line <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                        <input type="text" name="email_org_line" value="{{ old('email_org_line', $orgLine) }}" placeholder="Provincial Government of Cavite" class="input" maxlength="150">
                        <p class="text-xs text-gray-400 mt-1">Shown in the email header next to the logo, and in the footer disclaimer.</p>
                    </div>
                    <div>
                        <label class="label">Button label</label>
                        <input type="text" name="email_cta_label" value="{{ old('email_cta_label', $ctaLabel) }}" class="input" maxlength="60" required>
                        <p class="text-xs text-gray-400 mt-1">The call-to-action button text (e.g. "View My Documents").</p>
                    </div>
                    <div>
                        <label class="label">Footer disclaimer</label>
                        <input type="text" name="email_footer_text" value="{{ old('email_footer_text', $footerText) }}" class="input" maxlength="255" required>
                        <p class="text-xs text-gray-400 mt-1">Shown at the very bottom of every email, before the support line.</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <h2 class="font-semibold text-sm mb-3">Optional sections</h2>
                <div class="space-y-4">
                    <x-toggle name="email_show_logo" :checked="$showLogo" label="Show logo">
                        <span class="block text-xs text-gray-400 mt-0.5">Falls back to a text badge with the app's initial when no logo is uploaded in System Settings.</span>
                    </x-toggle>
                    <x-toggle name="email_show_cta" :checked="$showCta" label="Show call-to-action button">
                        <span class="block text-xs text-gray-400 mt-0.5">Links to the Document Tracking page.</span>
                    </x-toggle>
                    <x-toggle name="email_show_support_line" :checked="$showSupportLine" label="Show support contact line">
                        <span class="block text-xs text-gray-400 mt-0.5">Only prints when a Support contact is set in System Settings.</span>
                    </x-toggle>
                </div>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save Email Design</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
