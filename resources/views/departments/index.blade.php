<x-app-layout>
    <x-slot name="header">Departments</x-slot>

    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Top-level offices. Each department contains its own divisions and staff.</p>
            <x-btn :href="route('departments.create')">+ Add Department</x-btn>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($departments as $dept)
                <x-card>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-white text-xs font-bold" style="background: var(--color-primary)">{{ $dept->code }}</span>
                        @if($dept->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="gray">Inactive</x-badge>@endif
                    </div>
                    <h3 class="font-semibold mt-2">{{ $dept->name }}</h3>
                    @if($dept->description)<p class="text-xs text-gray-400 mt-1">{{ $dept->description }}</p>@endif
                    <div class="flex items-center gap-4 text-xs text-gray-400 mt-4">
                        <a href="{{ route('departments.edit', $dept) }}" class="hover:text-[color:var(--color-primary)]">🏢 {{ $dept->divisions_count }} divisions</a>
                        <a href="{{ route('users.index', ['department_id' => $dept->id]) }}" class="hover:text-[color:var(--color-primary)]">👤 {{ $dept->users_count }} users</a>
                        <span>📄 {{ $dept->documents_count }} docs</span>
                    </div>
                    <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <x-edit-button :href="route('departments.edit', $dept)" />
                        <x-delete-button :action="route('departments.destroy', $dept)" confirm="Delete department {{ $dept->code }}?" />
                    </div>
                </x-card>
            @empty
                <p class="text-sm text-gray-400">No departments yet.</p>
            @endforelse
        </div>
        @if($departments->hasPages()){{ $departments->links() }}@endif
    </div>
</x-app-layout>
