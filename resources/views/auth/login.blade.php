<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Username or Email -->
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Username or Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username"
                       placeholder="Enter your username"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 pl-11 pr-4 py-3 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 outline-none transition focus:bg-white dark:focus:bg-gray-900 focus:border-[color:var(--color-primary)] focus:ring-4 focus:ring-[color:var(--color-primary)]/15">
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
                <input id="password" name="password" required autocomplete="current-password"
                       x-bind:type="show ? 'text' : 'password'" type="password"
                       placeholder="Enter your password"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 pl-11 pr-11 py-3 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 outline-none transition focus:bg-white dark:focus:bg-gray-900 focus:border-[color:var(--color-primary)] focus:ring-4 focus:ring-[color:var(--color-primary)]/15">
                <button type="button" @click="show = !show" tabindex="-1"
                        :aria-label="show ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary)]">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-medium hover:underline" style="color: var(--color-primary)" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit"
                class="group w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-white text-sm font-semibold shadow-lg shadow-[color:var(--color-primary)]/25 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-4 focus:ring-[color:var(--color-primary)]/30 transition-all duration-200"
                style="background-image: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark, #1e293b));">
            Sign in
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>
</x-guest-layout>
