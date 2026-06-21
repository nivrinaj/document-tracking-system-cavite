@props(['status'])

@php
    $colors = [
        'draft' => 'gray', 'released' => 'amber', 'received' => 'blue',
        'forwarded' => 'indigo', 'archived' => 'green', 'completed' => 'green',
    ];
    $color = $colors[$status] ?? 'gray';
@endphp

<x-badge :color="$color">{{ \App\Models\Document::statusLabel($status) }}</x-badge>
