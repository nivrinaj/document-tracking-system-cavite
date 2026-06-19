<x-app-layout>
    <x-slot name="header">Divisions</x-slot>

    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Organizational divisions within the department.</p>
            <x-btn :href="route('divisions.create')">+ Add Division</x-btn>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($divisions as $div)
                <x-card>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-white text-xs font-bold" style="background: var(--color-primary)">{{ $div->code }}</span>
                                @if($div->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="gray">Inactive</x-badge>@endif
                            </div>
                            <h3 class="font-semibold mt-2">{{ $div->name }}</h3>
                            @if($div->description)<p class="text-xs text-gray-400 mt-1">{{ $div->description }}</p>@endif
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-400 mt-4">
                        <span>👤 {{ $div->users_count }} users</span>
                        <span>📄 {{ $div->documents_count }} documents</span>
                    </div>
                    <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('divisions.edit', $div) }}" class="link text-sm">Edit</a>
                        <form method="POST" action="{{ route('divisions.destroy', $div) }}" data-confirm="Delete this division?">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm">Delete</button>
                        </form>
                    </div>
                </x-card>
            @empty
                <p class="text-sm text-gray-400">No divisions yet.</p>
            @endforelse
        </div>
        @if($divisions->hasPages()){{ $divisions->links() }}@endif
    </div>
</x-app-layout>
