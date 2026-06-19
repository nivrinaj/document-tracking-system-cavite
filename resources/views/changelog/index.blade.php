<x-app-layout>
    <x-slot name="header">Changelog</x-slot>

    <div class="max-w-3xl mx-auto space-y-4">
        <x-card>
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h1 class="text-lg font-semibold">What's new</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Every batch of changes, newest first.</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-white text-sm font-semibold" style="background: var(--color-primary)">
                        Version {{ $version }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">Released {{ $released }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <article class="prose-doc">
                {!! $html !!}
            </article>
        </x-card>
    </div>
</x-app-layout>
