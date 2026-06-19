<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage staff accounts, divisions and roles.</p>
            <x-btn :href="route('users.create')">+ Add User</x-btn>
        </div>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, username or email…" class="input">
                <select name="division_id" class="input">
                    <option value="">All divisions</option>
                    @foreach($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id')==$d->id)>{{ $d->name }}</option>@endforeach
                </select>
                <select name="role" class="input">
                    <option value="">All roles</option>
                    @foreach($roles as $r)<option value="{{ $r->name }}" @selected(request('role')===$r->name)>{{ $r->name }}</option>@endforeach
                </select>
                <select name="status" class="input">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
                <div class="sm:col-span-2 lg:col-span-4 flex gap-2"><x-btn type="submit">Filter</x-btn><x-btn :href="route('users.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr><th class="table-th">Name</th><th class="table-th">Division</th><th class="table-th">Role</th><th class="table-th">Status</th><th class="table-th text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="table-td" data-label="Name">
                                    <div class="flex items-center gap-3 justify-end sm:justify-start">
                                        <img src="{{ $user->avatar_url }}" class="w-8 h-8 rounded-full">
                                        <div>
                                            <div class="font-medium">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-td" data-label="Division">{{ $user->division?->code ?? '—' }}</td>
                                <td class="table-td" data-label="Role">
                                    @foreach($user->roles as $r)<x-badge color="indigo">{{ $r->name }}</x-badge>@endforeach
                                </td>
                                <td class="table-td" data-label="Status">
                                    @if($user->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="red">Inactive</x-badge>@endif
                                </td>
                                <td class="table-td text-right whitespace-nowrap" data-label="">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
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
