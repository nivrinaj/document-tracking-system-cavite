<x-app-layout>
    <x-slot name="header">My Profile</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>

        @if(($settings['enable_user_delete'] ?? '1') === '1')
        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </x-card>
        @endif
    </div>
</x-app-layout>
