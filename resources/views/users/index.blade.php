<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage staff accounts, divisions and roles.</p>
            <x-btn :href="route('users.create')">+ Add User</x-btn>
        </div>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…" class="input">
                <select name="division_id" class="input">
                    <option value="">All divisions</option>
                    @foreach($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id')==$d->id)>{{ $d->name }}</option>@endforeach
                </select>
                <div class="flex gap-2"><x-btn type="submit" class="flex-1">Filter</x-btn><x-btn :href="route('users.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr><th class="table-th">Name</th><th class="table-th">Division</th><th class="table-th">Role</th><th class="table-th">Status</th><th class="table-th"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $user->avatar_url }}" class="w-8 h-8 rounded-full">
                                        <div>
                                            <div class="font-medium">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-td">{{ $user->division?->code ?? '—' }}</td>
                                <td class="table-td">
                                    @foreach($user->roles as $r)<x-badge color="indigo">{{ $r->name }}</x-badge>@endforeach
                                </td>
                                <td class="table-td">
                                    @if($user->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="red">Inactive</x-badge>@endif
                                </td>
                                <td class="table-td text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('users.edit', $user) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this user?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $users->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
