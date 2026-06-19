<x-app-layout>
    <x-slot name="header">Roles &amp; Permissions</x-slot>

    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Define what each role can do.</p>
            <x-btn :href="route('roles.create')">+ Add Role</x-btn>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($roles as $role)
                <x-card>
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold">{{ $role->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $role->users_count }} user(s) · {{ $role->permissions->count() }} permission(s)</p>
                        </div>
                        <div class="flex gap-2">
                            <x-edit-button :href="route('roles.edit', $role)" />
                            @if(!in_array($role->name, ['Super Admin','Department Head','Assistant Department Head','Receiving Staff','Staff']))
                                <x-delete-button :action="route('roles.destroy', $role)" confirm="Delete the role {{ $role->name }}?" />
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-3">
                        @forelse($role->permissions->take(12) as $perm)
                            <span class="text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $perm->name }}</span>
                        @empty
                            <span class="text-xs text-gray-400">No explicit permissions @if($role->name==='Super Admin')(full access via Super Admin)@endif</span>
                        @endforelse
                        @if($role->permissions->count() > 12)<span class="text-[11px] text-gray-400">+{{ $role->permissions->count()-12 }} more</span>@endif
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>
</x-app-layout>
