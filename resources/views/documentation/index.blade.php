<x-app-layout>
    <x-slot name="header">Documentation</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Sidebar list --}}
        <div class="lg:col-span-1">
            <x-card padding="p-3">
                <div class="flex items-center justify-between px-2 mb-2">
                    <span class="text-xs font-semibold uppercase text-gray-400">Guides</span>
                    @can('documentation.manage')
                        <a href="{{ route('documentation.create') }}" class="link text-xs">+ New</a>
                    @endcan
                </div>
                @forelse($grouped as $category => $pages)
                    <div class="mb-3">
                        <div class="px-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $category }}</div>
                        <ul class="mt-1">
                            @foreach($pages as $page)
                                <li>
                                    <a href="{{ route('documentation.index', ['page' => $page->slug]) }}"
                                       class="block px-2 py-1.5 rounded-lg text-sm {{ $current && $current->id === $page->id ? 'text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                       @if($current && $current->id === $page->id) style="background: var(--color-primary)" @endif>
                                        {{ $page->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-2 py-4 text-sm text-gray-400">No documentation yet. @can('documentation.manage')<a href="{{ route('documentation.create') }}" class="link">Create the first page</a>.@endcan</p>
                @endforelse
            </x-card>
        </div>

        {{-- Content --}}
        <div class="lg:col-span-3">
            <x-card>
                @if($current)
                    <div class="flex items-start justify-between gap-3 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <div class="text-xs text-gray-400 uppercase tracking-wider">{{ $current->category }}</div>
                            <h1 class="text-xl font-semibold">{{ $current->title }}</h1>
                            @if($current->excerpt)<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $current->excerpt }}</p>@endif
                        </div>
                        @can('documentation.manage')
                            <div class="flex gap-2 shrink-0">
                                <x-btn :href="route('documentation.edit', $current)" variant="secondary">Edit</x-btn>
                                <form method="POST" action="{{ route('documentation.destroy', $current) }}" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-2 text-sm text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                    <article class="prose-doc">
                        {!! $current->renderedHtml() !!}
                    </article>
                @else
                    <p class="text-sm text-gray-400">Select a guide from the left.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
