@props(['name' => null, 'label' => null, 'description' => null])

{{--
    Modern on/off toggle. Works as a normal form field (hidden 0 + checkbox 1,
    same pattern as the old checkboxes) AND with Alpine via x-model — pass any of
    x-model / @checked / @click through as attributes; they land on the checkbox.
--}}
<label class="flex items-start gap-3 cursor-pointer select-none">
    <span class="relative inline-flex shrink-0 mt-0.5">
        @if($name)<input type="hidden" name="{{ $name }}" value="0">@endif
        <input type="checkbox" @if($name) name="{{ $name }}" value="1" @endif {{ $attributes->merge(['class' => 'peer sr-only']) }}>
        <span class="w-10 h-6 rounded-full bg-gray-300 dark:bg-gray-600 transition-colors peer-checked:bg-[color:var(--color-primary)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[color:var(--color-primary)] dark:peer-focus-visible:ring-offset-gray-800"></span>
        <span class="pointer-events-none absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
    </span>
    @if($label || $description || trim($slot ?? '') !== '')
        <span class="min-w-0 leading-tight">
            @if($label)<span class="block text-sm font-medium">{{ $label }}</span>@endif
            {{ $slot }}
            @if($description)<span class="block text-xs text-gray-400 mt-0.5">{{ $description }}</span>@endif
        </span>
    @endif
</label>
