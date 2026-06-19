<x-app-layout>
    <x-slot name="header">Edit Division</x-slot>
    <div class="max-w-xl mx-auto">
        <x-card>
            <form method="POST" action="{{ route('divisions.update', $division) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('divisions._form', ['division' => $division])
                @php $backUrl = $division->department_id ? route('departments.edit', $division->department_id) : route('departments.index'); @endphp
                <div class="flex justify-end gap-2"><x-btn :href="$backUrl" variant="secondary">Cancel</x-btn><x-btn type="submit">Save</x-btn></div>
            </form>
        </x-card>
    </div>
</x-app-layout>
