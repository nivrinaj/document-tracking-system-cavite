<x-app-layout>
    <x-slot name="header">Edit Department</x-slot>
    <div class="max-w-3xl mx-auto space-y-6">
        <x-card title="Department details">
            <form method="POST" action="{{ route('departments.update', $department) }}" class="space-y-4">
                @csrf @method('PUT')
                @include('departments._form', ['department' => $department])
                <div class="flex justify-end gap-2">
                    <x-btn :href="route('departments.index')" variant="secondary">Back</x-btn>
                    <x-btn type="submit">Save</x-btn>
                </div>
            </form>
        </x-card>

        {{-- Divisions belong to this department --}}
        <x-card title="Divisions in this department">
            <div class="overflow-x-auto -mx-1">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-4">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr><th class="table-th">Code</th><th class="table-th">Name</th><th class="table-th">Staff</th><th class="table-th text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($department->divisions as $div)
                            <tr>
                                <td class="table-td font-medium" data-label="Code">{{ $div->code }}</td>
                                <td class="table-td" data-label="Name">{{ $div->name }}</td>
                                <td class="table-td" data-label="Staff">{{ $div->users_count }}</td>
                                <td class="table-td text-right whitespace-nowrap" data-label="">
                                    <a href="{{ route('divisions.edit', $div) }}" class="link text-sm">Edit</a>
                                    <form method="POST" action="{{ route('divisions.destroy', $div) }}" class="inline ml-2" data-confirm="Delete division {{ $div->code }}?">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400">No divisions yet — add one below.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Inline add division --}}
            <form method="POST" action="{{ route('divisions.store') }}" class="border-t border-gray-100 dark:border-gray-700 pt-4">
                @csrf
                <input type="hidden" name="department_id" value="{{ $department->id }}">
                <input type="hidden" name="is_active" value="1">
                <div class="grid grid-cols-1 sm:grid-cols-[120px_1fr_auto] gap-2 items-end">
                    <div>
                        <label class="label">Code</label>
                        <input type="text" name="code" class="input" placeholder="ISDA" required>
                    </div>
                    <div>
                        <label class="label">Division name</label>
                        <input type="text" name="name" class="input" placeholder="Information Systems & Database Admin" required>
                    </div>
                    <x-btn type="submit">+ Add</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
