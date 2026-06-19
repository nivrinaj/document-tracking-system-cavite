<x-app-layout>
    <x-slot name="header">Add Document Type</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('document-types.store') }}" class="space-y-4">
                @csrf
                @include('document_types._form', ['type' => null])
                <div class="flex justify-end gap-2"><x-btn :href="route('document-types.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Create</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
