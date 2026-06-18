<x-app-layout>
    <x-slot name="header">QR Code</x-slot>

    <div class="max-w-md mx-auto">
        <x-card padding="p-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-lg font-semibold">QR code not found</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    This document is not associated with your account, or the code is invalid.
                    If you believe this document was assigned to you, please make sure you are logged in
                    with the correct account, or contact the receiving staff.
                </p>
                <div class="mt-6 flex flex-col gap-2">
                    <x-btn :href="route('dashboard')" class="w-full">Go to my dashboard</x-btn>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-sm text-gray-500 hover:underline">Log in as a different user</button>
                    </form>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
