<x-app-layout>
    <x-slot name="header">New Documentation Page</x-slot>
    <div class="max-w-3xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('documentation.store') }}" class="space-y-4">
                @csrf
                @include('documentation._form')
                <div class="flex justify-end gap-2"><x-btn :href="route('documentation.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Create Page</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
