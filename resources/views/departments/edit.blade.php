<x-app-layout>
    <x-slot name="header">Edit Department</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('departments.update', $department) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('departments._form', ['department' => $department])
                <div class="flex justify-end gap-2"><x-btn :href="route('departments.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Save</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
