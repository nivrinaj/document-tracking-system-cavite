<x-app-layout>
    <x-slot name="header">Edit Document Type</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('document-types.update', $type) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('document_types._form', ['type' => $type])
                <div class="flex justify-end gap-2"><x-btn :href="route('document-types.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Save</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
