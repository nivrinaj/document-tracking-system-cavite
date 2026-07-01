@props(['name', 'value' => null, 'min' => 1, 'max' => 9999])

<div class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shrink-0 bg-white dark:bg-gray-800"
     x-data="{ qty: {{ (int) ($value ?: $min) }} }">
    <button type="button" @click="qty = Math.max({{ $min }}, (qty || {{ $min }}) - 1)"
            class="w-9 h-9 grid place-items-center text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors shrink-0" tabindex="-1">−</button>
    <input type="number" name="{{ $name }}" x-model.number="qty" min="{{ $min }}" max="{{ $max }}"
           {{ $attributes }}
           class="w-16 text-center border-x border-gray-200 dark:border-gray-700 bg-transparent py-2 text-sm font-semibold focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
    <button type="button" @click="qty = Math.min({{ $max }}, (qty || 0) + 1)"
            class="w-9 h-9 grid place-items-center text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors shrink-0" tabindex="-1">+</button>
</div>
