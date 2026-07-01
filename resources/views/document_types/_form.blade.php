<div class="max-w-md">
    <label class="label">Type Name <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $type?->name) }}" class="input" placeholder="e.g. Voucher" required>
    <p class="text-[11px] text-gray-400 mt-1">All document types are available to every office. To limit a specific office to only certain types, use <strong>Departments → edit → “Limit to document types”</strong>.</p>
</div>

<div class="mt-5 space-y-4" x-data="{
        transmittal: {{ old('allows_transmittal', $type?->allows_transmittal) ? 'true' : 'false' }},
        tScope: '{{ old('transmittal_scope', $type?->transmittal_scope ?? 'all') }}',
    }">
    <x-toggle name="requires_voucher" :checked="old('requires_voucher', $type?->requires_voucher)" label="Show a legacy voucher number field for this type" />

    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <x-toggle name="requires_deadline" :checked="old('requires_deadline', $type?->requires_deadline)" label="Show a Deadline field for this type">
            <span class="block text-xs text-gray-400 mt-0.5">e.g. Letter — only in offices with deadlines enabled.</span>
        </x-toggle>
    </div>

    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <x-toggle name="allows_transmittal" x-model="transmittal" label="Allow encoding this as a transmittal">
            <span class="block text-xs text-gray-400 mt-0.5">Multiple documents under one tracking code — adds a quantity field at encode time.</span>
        </x-toggle>

        <div x-show="transmittal" x-cloak class="ml-[3.25rem] mt-3 space-y-3">
            <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 w-fit text-sm">
                <button type="button" @click="tScope = 'all'" class="px-3 py-1.5 rounded-md transition-colors" :class="tScope === 'all' ? 'bg-[color:var(--color-primary)] text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">All offices</button>
                <button type="button" @click="tScope = 'selected'" class="px-3 py-1.5 rounded-md transition-colors" :class="tScope === 'selected' ? 'bg-[color:var(--color-primary)] text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">Select offices</button>
            </div>
            <input type="hidden" name="transmittal_scope" :value="tScope">

            <div x-show="tScope === 'selected'" x-cloak>
                <p class="text-xs text-gray-400 mb-1.5">Only these offices can encode this type as a transmittal; everyone else encodes it normally.</p>
                <div class="max-w-md" x-data="multiSelect({
                    items: @js($departments->map(fn($d) => ['id' => (string) $d->id, 'label' => $d->code.' — '.$d->name])),
                    selected: @js(array_map('strval', array_filter(explode(',', (string) ($type?->transmittal_departments ?? ''))))),
                    name: 'transmittal_departments[]',
                    placeholder: '— Select offices —',
                })">
                    <x-reports._multi-select />
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <x-toggle name="is_active" :checked="old('is_active', $type?->is_active ?? true)" label="Active" />
    </div>
</div>
