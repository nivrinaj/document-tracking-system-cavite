@props(['label' => '', 'value' => 0, 'color' => 'primary', 'icon' => null])

@php
    $bg = [
        'primary' => 'bg-[color:var(--color-primary)]',
        'amber'   => 'bg-amber-500',
        'green'   => 'bg-green-500',
        'red'     => 'bg-red-500',
        'blue'    => 'bg-blue-500',
    ][$color] ?? 'bg-gray-500';
@endphp

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white {{ $bg }}"
         @if($color === 'primary') style="background: var(--color-primary)" @endif>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            {{ $icon }}
        </svg>
    </div>
    <div>
        <div class="text-2xl font-bold leading-none">{{ $value }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $label }}</div>
    </div>
</div>
