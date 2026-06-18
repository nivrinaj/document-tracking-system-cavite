<x-app-layout>
    <x-slot name="header">Add Role</x-slot>
    <div class="max-w-3xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
                @csrf
                @include('roles._form')
                <div class="flex justify-end gap-2"><x-btn :href="route('roles.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Create Role</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
