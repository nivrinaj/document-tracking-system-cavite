<x-app-layout>
    <x-slot name="header">Edit Division</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('divisions.update', $division) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('divisions._form', ['division' => $division])
                <div class="flex justify-end gap-2"><x-btn :href="route('divisions.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Save</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
