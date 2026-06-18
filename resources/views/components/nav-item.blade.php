@props(['active' => false, 'href' => '#', 'label' => ''])

<a href="{{ $href }}"
   @if($active) style="background: var(--color-primary)" @endif
   {{ $attributes->merge(['class' => 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition '.($active ? 'text-white shadow' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700')]) }}>
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
        {{ $slot }}
    </svg>
    <span class="truncate">{{ $label }}</span>
</a>
