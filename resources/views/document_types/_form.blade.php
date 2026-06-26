@php $selectedDepts = old('departments', isset($type) ? $type->departments->pluck('id')->all() : []); @endphp
<div x-data="{ availability: '{{ old('availability', $type->availability ?? 'all') }}' }">
    <div>
        <label class="label">Type Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $type?->name) }}" class="input sm:max-w-sm" placeholder="e.g. Voucher" required>
    </div>

    <div class="mt-5">
        <label class="label">Which offices can encode this type?</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
            @foreach(['all' => ['All offices', 'Every department can use it'], 'restricted' => ['Only selected offices', 'Pick the offices below']] as $val => [$t, $d])
                <label class="cursor-pointer">
                    <input type="radio" name="availability" value="{{ $val }}" x-model="availability" class="peer sr-only">
                    <span class="block rounded-xl border border-gray-200 dark:border-gray-600 p-3 transition peer-checked:border-[color:var(--color-primary)] peer-checked:ring-2 peer-checked:ring-[color:var(--color-primary)]/30 peer-checked:bg-[color:var(--color-primary)]/5">
                        <span class="block text-sm font-medium text-gray-800 dark:text-gray-100">{{ $t }}</span>
                        <span class="block text-xs text-gray-400">{{ $d }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <div x-show="availability === 'restricted'" x-cloak class="mt-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 max-w-xl">
            <p class="text-xs text-gray-400 mb-2">Tick the offices that may encode this document type “for now”. (This is separate from the Accounting amount fields, which follow the office's Accounting flag.)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-56 overflow-y-auto">
                @foreach($departments as $dept)
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="checkbox" name="departments[]" value="{{ $dept->id }}" class="rounded text-[color:var(--color-primary)]" @checked(in_array($dept->id, $selectedDepts))>
                        <span>{{ $dept->code }} <span class="text-gray-400">— {{ $dept->name }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm mt-5">
        <input type="hidden" name="requires_voucher" value="0">
        <input type="checkbox" name="requires_voucher" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('requires_voucher', $type?->requires_voucher))>
        Show a <strong>voucher number</strong> field for this type (legacy; non-Accounting offices)
    </label>
    <label class="flex items-center gap-2 text-sm mt-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked(old('is_active', $type?->is_active ?? true))>
        Active
    </label>
</div>
