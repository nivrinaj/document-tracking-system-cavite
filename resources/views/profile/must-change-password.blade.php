<x-guest-layout>
    <div class="mb-5 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-[color:var(--color-primary)]/10 grid place-items-center mb-3">
            <svg class="w-6 h-6" style="color: var(--color-primary)" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h2 class="text-lg font-semibold">Choose a new password</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">For your account's security, please set your own password before continuing.</p>
    </div>

    <x-input-error :messages="$errors->get('password')" class="mb-3" />

    <form method="POST" action="{{ route('password.mustChange.update') }}" class="space-y-4"
          x-data="{
              pw: '',
              confirm: '',
              show: false,
              get hasLower() { return /[a-z]/.test(this.pw); },
              get hasUpper() { return /[A-Z]/.test(this.pw); },
              get hasNumber() { return /[0-9]/.test(this.pw); },
              get hasLength() { return this.pw.length >= 8; },
              get matches() { return this.confirm.length > 0 && this.pw === this.confirm; },
          }">
        @csrf
        @method('PUT')

        <div>
            <label for="password" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">New Password</label>
            <div class="relative">
                <input id="password" name="password" x-model="pw" required autocomplete="new-password"
                       x-bind:type="show ? 'text' : 'password'" type="password"
                       placeholder="Type your new password"
                       class="w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/40 pl-4 pr-11 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 outline-none transition focus:border-[color:var(--color-primary)] focus:ring-2 focus:ring-[color:var(--color-primary)]/20">
                <button type="button" @click="show = !show" tabindex="-1"
                        :aria-label="show ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" x-model="confirm" required autocomplete="new-password"
                   x-bind:type="show ? 'text' : 'password'" type="password"
                   placeholder="Type it again to confirm"
                   class="w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/40 pl-4 pr-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 outline-none transition focus:border-[color:var(--color-primary)] focus:ring-2 focus:ring-[color:var(--color-primary)]/20">
            <p class="mt-1.5 text-xs" x-show="confirm.length > 0">
                <span x-show="matches" class="text-green-600 dark:text-green-400">✓ Passwords match.</span>
                <span x-show="!matches" class="text-red-600 dark:text-red-400">✗ Doesn't match yet.</span>
            </p>
        </div>

        {{-- Plain-language checklist — easier to follow than a rules paragraph. --}}
        <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 p-3">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">Your password needs:</p>
            <ul class="space-y-1 text-xs">
                <li class="flex items-center gap-1.5" :class="hasLength ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                    <span x-text="hasLength ? '✓' : '○'"></span> At least 8 characters
                </li>
                <li class="flex items-center gap-1.5" :class="hasLower ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                    <span x-text="hasLower ? '✓' : '○'"></span> A lowercase letter (a–z)
                </li>
                <li class="flex items-center gap-1.5" :class="hasUpper ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                    <span x-text="hasUpper ? '✓' : '○'"></span> An uppercase letter (A–Z)
                </li>
                <li class="flex items-center gap-1.5" :class="hasNumber ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                    <span x-text="hasNumber ? '✓' : '○'"></span> A number (0–9)
                </li>
            </ul>
            <p class="text-[11px] text-gray-400 mt-2">Tip: a word you'll remember plus a number and a capital letter works well — e.g. <span class="font-mono">Records2026</span>.</p>
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-md hover:shadow-lg hover:brightness-110 active:brightness-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)]/40 transition-all duration-150"
                style="background: var(--color-primary);">
            Set new password &amp; continue
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">Not you? Sign out</button>
    </form>
</x-guest-layout>
