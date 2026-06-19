<x-app-layout>
    <x-slot name="header">Add Department</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('departments.store') }}" class="space-y-4">
                @csrf
                @include('departments._form', ['department' => null])
                <div class="flex justify-end gap-2"><x-btn :href="route('departments.index')" variant="secondary">Cancel</x-btn><x-btn type="submit">Create</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
