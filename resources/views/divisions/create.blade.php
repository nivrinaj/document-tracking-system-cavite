<x-app-layout>
    <x-slot name="header">Add Division</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('divisions.store') }}" class="space-y-4">
                @csrf
                @include('divisions._form', ['division' => null])
                <div class="flex justify-end gap-2"><x-btn :href="route('divisions.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Create</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
