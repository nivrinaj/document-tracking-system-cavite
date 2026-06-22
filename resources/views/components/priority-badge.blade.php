@props(['priority'])

@if(\App\Models\Document::priorityEnabled() && $priority)
    @php
        $colors = ['urgent' => 'red', 'high' => 'orange', 'normal' => 'blue', 'low' => 'gray'];
        $color = $colors[$priority] ?? 'gray';
    @endphp
    <x-badge :color="$color">{{ ucfirst($priority) }}</x-badge>
@endif
