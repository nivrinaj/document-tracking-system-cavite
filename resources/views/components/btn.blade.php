@props(['href' => null, 'type' => 'button', 'variant' => 'primary'])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50';
    $variants = [
        'primary'   => 'text-white shadow-sm hover:opacity-90',
        'secondary' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700',
        'success'   => 'bg-green-600 text-white hover:bg-green-700',
        'outline'   => 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
    $style = $variant === 'primary' ? 'background: var(--color-primary)' : '';
@endphp

@if($href)
    <a href="{{ $href }}" style="{{ $style }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" style="{{ $style }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
