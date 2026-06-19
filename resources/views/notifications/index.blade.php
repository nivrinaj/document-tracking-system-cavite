<x-app-layout>
    <x-slot name="header">Notifications</x-slot>

    <div class="max-w-3xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">Documents released or forwarded to you.</p>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                    <x-btn type="submit" variant="secondary">Mark all read</x-btn>
                </form>
            @endif
        </div>

        <x-card padding="p-0">
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($notifications as $n)
                    <li class="{{ $n->read_at ? '' : 'bg-blue-50/50 dark:bg-blue-900/10' }}">
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf
                            <button type="submit" class="w-full text-left px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-start gap-3">
                                <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $n->read_at ? 'bg-transparent' : 'bg-blue-500' }}"></span>
                                <span class="min-w-0">
                                    <span class="block text-sm">{{ $n->data['message'] ?? 'Notification' }}</span>
                                    @if(!empty($n->data['remarks']))<span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">“{{ $n->data['remarks'] }}”</span>@endif
                                    <span class="block text-xs text-gray-400 mt-1">{{ $n->data['tracking_code'] ?? '' }} · {{ $n->created_at->format('M d, Y g:i A') }} · {{ $n->created_at->diffForHumans() }}</span>
                                </span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-gray-400">No notifications yet.</li>
                @endforelse
            </ul>
        </x-card>

        @if($notifications->hasPages()){{ $notifications->links() }}@endif
    </div>
</x-app-layout>
