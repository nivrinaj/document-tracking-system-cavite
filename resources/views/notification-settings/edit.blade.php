<x-app-layout>
    <x-slot name="header">Notification Settings</x-slot>

    <div class="space-y-5">
        <form method="POST" action="{{ route('notification-settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-card title="Email (SMTP)">
                <div class="space-y-4">
                    <x-toggle name="mail_enabled" label="Enable email notifications" :checked="$mail['enabled']"
                              description="Master switch. Off by default — no email is ever sent until this is on and the settings below are saved." />

                    <details class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <summary class="text-sm font-medium cursor-pointer select-none">How to find these values for a cavite.gov.ph mailbox (cPanel)</summary>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 space-y-1.5">
                            <p>1. Log into cPanel → <strong>Email Accounts</strong>.</p>
                            <p>2. Find the mailbox you want to send from (e.g. <code>notifications@cavite.gov.ph</code>) and click <strong>Connect Devices</strong> (or "Set Up Mail Client").</p>
                            <p>3. cPanel shows the exact <strong>Outgoing (SMTP) Server</strong>, port, and encryption (SSL/TLS) for your account — use those values below rather than guessing, since they vary by hosting provider. Typical values are port <code>465</code> with SSL, or <code>587</code> with TLS.</p>
                            <p>4. Username is the full email address; password is that mailbox's own password (not your cPanel account password).</p>
                            <p>5. Save below, then use "Send test email" to confirm it actually works before relying on it.</p>
                        </div>
                    </details>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">SMTP host</label>
                            <input type="text" name="mail_host" value="{{ old('mail_host', $mail['host']) }}" placeholder="mail.cavite.gov.ph" class="input">
                        </div>
                        <div>
                            <label class="label">Port</label>
                            <input type="number" name="mail_port" value="{{ old('mail_port', $mail['port']) }}" placeholder="587" class="input">
                        </div>
                        <div>
                            <label class="label">Encryption</label>
                            <select name="mail_encryption" class="input">
                                <option value="tls" @selected($mail['encryption']==='tls')>TLS</option>
                                <option value="ssl" @selected($mail['encryption']==='ssl')>SSL</option>
                                <option value="" @selected($mail['encryption']==='')>None</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Username</label>
                            <input type="text" name="mail_username" value="{{ old('mail_username', $mail['username']) }}" placeholder="notifications@cavite.gov.ph" class="input" autocomplete="off">
                        </div>
                        <div>
                            <label class="label">Password <span class="text-gray-400 text-xs font-normal">{{ $mail['has_password'] ? '(leave blank to keep the current one)' : '' }}</span></label>
                            <input type="password" name="mail_password" value="" placeholder="{{ $mail['has_password'] ? '••••••••' : '' }}" class="input" autocomplete="new-password">
                        </div>
                        <div></div>
                        <div>
                            <label class="label">From address <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $mail['from_address']) }}" placeholder="{{ $mail['username'] ?: 'notifications@cavite.gov.ph' }}" class="input">
                        </div>
                        <div>
                            <label class="label">From name <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $mail['from_name']) }}" placeholder="{{ $settings['app_name'] ?? 'PGC-DTS' }}" class="input">
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Notification types">
                <p class="text-xs text-gray-400 -mt-1 mb-4">Turn on the notifications you want sent, and how often. New types added later will appear here automatically.</p>
                <div class="space-y-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($types as $key => $meta)
                        @php $cfg = $config[$key]; @endphp
                        <div class="pt-4 first:pt-0 flex flex-wrap items-start justify-between gap-3">
                            <x-toggle name="notify[{{ $key }}][enabled]" :checked="$cfg['enabled']" label="{{ $meta['label'] }}" description="{{ $meta['description'] }}" />
                            <div class="flex items-center gap-2 shrink-0">
                                <select name="notify[{{ $key }}][frequency]" class="input py-1.5 text-sm">
                                    @foreach($meta['frequency_options'] as $val => $label)
                                        <option value="{{ $val }}" @selected($cfg['frequency']===$val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="flex justify-end">
                <x-btn type="submit">Save Settings</x-btn>
            </div>
        </form>

        <x-card title="Send a test email">
            <form method="POST" action="{{ route('notification-settings.test') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[240px]">
                    <label class="label">Send to</label>
                    <input type="email" name="test_email" required placeholder="you@cavite.gov.ph" class="input">
                </div>
                <x-btn type="submit" variant="secondary">Send Test Email</x-btn>
            </form>
        </x-card>

        <x-card title="Manually run a notification now">
            <p class="text-xs text-gray-400 -mt-1 mb-3">Skips the schedule and sends immediately — useful for testing without waiting for the daily run.</p>
            <div class="flex flex-wrap gap-2">
                @foreach($types as $key => $meta)
                    <form method="POST" action="{{ route('notification-settings.run', $key) }}" data-confirm="Send &quot;{{ $meta['label'] }}&quot; right now?">
                        @csrf
                        <x-btn type="submit" variant="secondary">Run "{{ $meta['label'] }}" now</x-btn>
                    </form>
                @endforeach
            </div>
        </x-card>
    </div>
</x-app-layout>
