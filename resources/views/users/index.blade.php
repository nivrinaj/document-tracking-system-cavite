<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage staff accounts, divisions and roles.</p>
            <x-btn :href="route('users.create')">+ Add User</x-btn>
        </div>

        <x-card padding="p-4">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
                  x-data="{
                      dept: '{{ request('department_id') }}', deptOpen: false, deptSearch: '',
                      divId: '{{ request('division_id') }}', divOpen: false, divSearch: '',
                      departments: @js($departments->map(fn($d)=>['id'=>$d->id,'name'=>$d->code])),
                      divisions: @js($divisions->map(fn($d)=>['id'=>$d->id,'name'=>$d->code.' — '.$d->name,'department_id'=>$d->department_id])),
                      get visibleDivs() { return this.divisions.filter(d => !this.dept || String(d.department_id) === String(this.dept)); },
                      get filteredDepts() { const q = this.deptSearch.toLowerCase().trim(); return this.departments.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get filteredDivs() { const q = this.divSearch.toLowerCase().trim(); return this.visibleDivs.filter(d => !q || d.name.toLowerCase().includes(q)); },
                      get deptLabel() { const d = this.departments.find(x => String(x.id) === String(this.dept)); return d ? d.name : 'All departments'; },
                      get divLabel() { const d = this.divisions.find(x => String(x.id) === String(this.divId)); return d ? d.name : 'All divisions'; },
                  }">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, username or email…" class="input">
                <div class="relative" @click.outside="deptOpen = false">
                    <input type="hidden" name="department_id" :value="dept">
                    <button type="button" @click="deptOpen = !deptOpen; deptSearch = ''" class="input-btn text-left pr-14 block">
                        <span class="truncate block" :class="!dept ? 'text-gray-400' : ''" x-text="deptLabel"></span>
                    </button>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                        <button type="button" x-show="dept" x-cloak @click.stop="dept = ''; divId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="deptOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div x-show="deptOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="deptSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                        <div class="max-h-56 overflow-y-auto py-1 text-sm">
                            <button type="button" @click="dept = ''; divId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All departments</button>
                            <template x-for="d in filteredDepts" :key="d.id"><button type="button" @click="dept = String(d.id); divId = ''; deptOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                        </div>
                    </div>
                </div>
                <div class="relative" @click.outside="divOpen = false">
                    <input type="hidden" name="division_id" :value="divId">
                    <button type="button" @click="divOpen = !divOpen; divSearch = ''" class="input-btn text-left pr-14 block">
                        <span class="truncate block" :class="!divId ? 'text-gray-400' : ''" x-text="divLabel"></span>
                    </button>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                        <button type="button" x-show="divId" x-cloak @click.stop="divId = ''" class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 pointer-events-none transition-transform" :class="divOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div x-show="divOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700"><input type="text" x-model="divSearch" @click.stop class="input py-1.5 text-sm" placeholder="Search…"></div>
                        <div class="max-h-56 overflow-y-auto py-1 text-sm">
                            <button type="button" @click="divId = ''; divOpen = false" class="w-full text-left px-3 py-1.5 text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50">All divisions</button>
                            <template x-for="d in filteredDivs" :key="d.id"><button type="button" @click="divId = String(d.id); divOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="d.name"></button></template>
                        </div>
                    </div>
                </div>
                <select name="role" class="input">
                    <option value="">All roles</option>
                    @foreach($roles as $r)<option value="{{ $r->name }}" @selected(request('role')===$r->name)>{{ $r->name }}</option>@endforeach
                </select>
                <select name="status" class="input">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
                <select name="per_page" class="input" onchange="this.form.submit()">
                    @foreach([12, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage == $n)>{{ $n }} rows</option>
                    @endforeach
                </select>
                <div class="sm:col-span-2 lg:col-span-4 flex gap-2"><x-btn type="submit">Filter</x-btn><x-btn :href="route('users.index')" variant="secondary">Reset</x-btn></div>
            </form>
        </x-card>

        @php
            $canBulkDelete = ($settings['enable_user_delete'] ?? '1') === '1';
            $selectableIds = $users->filter(fn ($u) => $u->id !== auth()->id() && ! $u->hasSystemRole(\App\Models\User::SYS_SUPER_ADMIN))->pluck('id');
        @endphp
        <x-card padding="p-0" x-data="{ selected: [], allIds: @js($selectableIds) }">
            @if($canBulkDelete)
            <div x-show="selected.length > 0" x-cloak class="flex items-center justify-between gap-3 px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-b border-gray-100 dark:border-gray-700">
                <span class="text-sm font-medium" x-text="selected.length + ' selected'"></span>
                <form method="POST" action="{{ route('users.bulkDestroy') }}" data-confirm="Delete the selected user(s)? This cannot be undone.">
                    @csrf
                    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <x-btn type="submit" variant="danger">Delete Selected</x-btn>
                </form>
            </div>
            @endif
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            @if($canBulkDelete)
                            <th class="table-th w-8">
                                <input type="checkbox" class="rounded" :checked="allIds.length > 0 && selected.length === allIds.length" @change="selected = $event.target.checked ? [...allIds] : []">
                            </th>
                            @endif
                            <th class="table-th">Name</th><th class="table-th">Department</th><th class="table-th">Division</th><th class="table-th">Role</th><th class="table-th">Status</th><th class="table-th text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                @if($canBulkDelete)
                                <td class="table-td" data-label="">
                                    @if($user->id !== auth()->id() && ! $user->hasSystemRole(\App\Models\User::SYS_SUPER_ADMIN))
                                        <input type="checkbox" class="rounded" value="{{ $user->id }}" x-model="selected">
                                    @endif
                                </td>
                                @endif
                                <td class="table-td" data-label="Name">
                                    <div class="flex items-center gap-3 justify-end sm:justify-start">
                                        <img src="{{ $user->avatar_url }}" class="w-8 h-8 rounded-full">
                                        <div>
                                            <div class="font-medium">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $user->username }} · {{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-td" data-label="Department">{{ $user->department?->code ?? '—' }}</td>
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
                                        @if(($settings['enable_user_delete'] ?? '1') === '1')
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" data-confirm="Delete this user? This cannot be undone.">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canBulkDelete ? 7 : 6 }}" class="px-4 py-10 text-center text-sm text-gray-400">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $users->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
