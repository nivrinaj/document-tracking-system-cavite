@props(['color' => 'gray'])

@php
    $map = [
        'gray'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'red'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
        'amber'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'green'  => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'blue'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    ];
    $classes = $map[$color] ?? $map['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $slot }}
</span>
