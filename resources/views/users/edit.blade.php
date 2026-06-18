<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <div class="max-w-2xl mx-auto">
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
    </div>
</x-app-layout>
