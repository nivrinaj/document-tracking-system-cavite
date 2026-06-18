<x-app-layout>
    <x-slot name="header">Edit Documentation Page</x-slot>
    <div class="max-w-3xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('documentation.update', $page) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('documentation._form')
                <div class="flex justify-end gap-2"><x-btn :href="route('documentation.index', ['page' => $page->slug])" variant="secondary">Cancel</x-btn><x-btn type="submit">Save Changes</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
