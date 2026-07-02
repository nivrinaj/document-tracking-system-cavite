<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <div class="max-w-2xl mx-auto space-y-5">
        <x-card>
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @include('users._form', ['user' => $user])
                <div class="flex justify-end gap-2 pt-2">
                    <x-btn :href="route('users.index')" variant="secondary">Cancel</x-btn>
                    <x-btn type="submit">Save Changes</x-btn>
                </div>
            </form>
        </x-card>

        <x-card title="Reset Password">
            <p class="text-xs text-gray-400 mb-3">Resets {{ $user->name }}'s password to the default ("{{ \App\Models\User::DEFAULT_PASSWORD }}") and requires them to set their own on next login — useful for accounts created before the default-password option existed.</p>
            <form method="POST" action="{{ route('users.resetPassword', $user) }}" data-confirm="Reset {{ $user->name }}'s password to the default (&quot;{{ \App\Models\User::DEFAULT_PASSWORD }}&quot;)? They will be required to change it on their next login.">
                @csrf
                <x-btn type="submit" variant="secondary">Reset to Default Password</x-btn>
            </form>
        </x-card>
    </div>
</x-app-layout>
