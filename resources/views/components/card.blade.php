@props(['title' => null, 'padding' => 'p-5'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm']) }}>
    @if($title)
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 font-semibold text-sm">{{ $title }}</div>
        <div class="{{ $padding }}">{{ $slot }}</div>
    @else
        <div class="{{ $padding }}">{{ $slot }}</div>
    @endif
</div>
