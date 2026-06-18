<x-app-layout>
    <x-slot name="header">Add User</x-slot>

    <div class="max-w-2xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                @csrf
                @include('users._form', ['user' => null])
                <div class="flex justify-end gap-2 pt-2">
                    <x-btn :href="route('users.index')" variant="secondary">Cancel</x-btn>
                    <x-btn type="submit">Create User</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
