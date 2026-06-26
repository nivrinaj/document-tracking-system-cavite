<x-app-layout>
    <x-slot name="header">Accounting Setup</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">Reference data used when encoding Vouchers and Payroll — funds, responsibility centers and natures of transaction.</p>

        {{-- ───────── Overdue tracking ───────── --}}
        @if($department)
        <x-card>
            <h2 class="font-semibold mb-1">Overdue tracking</h2>
            <p class="text-xs text-gray-400 mb-3">Highlights documents in the tracking list once they pass a working-time limit — <span class="text-rose-600 dark:text-rose-400 font-medium">red</span> when overdue, <span class="text-amber-600 dark:text-amber-400 font-medium">orange</span> within 2 working days before. Counts working hours only (skips nights, weekends, holidays, leave).</p>
            <form method="POST" action="{{ route('accounting.overdue.update') }}" x-data="{ on: {{ $department->sla_enabled ? 'true' : 'false' }} }">
                @csrf @method('PUT')
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="sla_enabled" value="0">
                    <input type="checkbox" name="sla_enabled" value="1" x-model="on" class="rounded text-[color:var(--color-primary)]">
                    Enable overdue highlighting for <strong>{{ $department->code }}</strong>
                </label>
                <div x-show="on" x-cloak class="mt-4 space-y-4">
                    <div class="max-w-xs">
                        <label class="label">Overdue after (working days)</label>
                        <input type="number" name="sla_days" min="1" max="365" value="{{ old('sla_days', $department->sla_days ?? 7) }}" class="input" x-bind:required="on">
                    </div>
                    <div>
                        <label class="label">Track these document types <span class="text-gray-400 text-xs font-normal">— none ticked = all</span></label>
                        @php $tracked = (array) ($department->sla_document_type ?? []); @endphp
                        <div class="flex flex-wrap gap-2">
                            @forelse($trackableTypes as $tn)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="sla_document_type[]" value="{{ $tn }}" class="peer sr-only" @checked(in_array($tn, $tracked))>
                                    <span class="inline-flex px-3 py-1.5 rounded-lg text-sm border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 transition peer-checked:text-white peer-checked:border-transparent peer-checked:[background:var(--color-primary)]">{{ $tn }}</span>
                                </label>
                            @empty
                                <span class="text-xs text-gray-400">No document types available for this office yet.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="mt-4"><x-btn type="submit">Save overdue settings</x-btn></div>
            </form>
        </x-card>
        @endif

        {{-- ───────── Funds ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Funds</h2>
            <p class="text-xs text-gray-400 mb-3">The fund code prefixes the auto-generated tracking code. Every fund has its own annual sequence (starts at 1, resets each year). Add the “Gen. Fund 20% Development Fund” as its own fund (same code 101 is fine — it keeps a separate sequence). Tick “Hospital” for funds the Hospital division may use.</p>
            <div class="overflow-x-auto">
                <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40"><tr>
                        <th class="table-th">Name</th><th class="table-th">Code</th><th class="table-th">Hospital</th><th class="table-th">Active</th><th class="table-th text-right">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($funds as $fund)
                            <tr x-data="{ edit: false }">
                                <template x-if="!edit">
                                    <td class="table-td" data-label="Name">{{ $fund->name }}</td>
                                </template>
                                <td class="table-td" data-label="Code">{{ $fund->code }}</td>
                                <td class="table-td" data-label="Hospital">{!! $fund->hospital_available ? '✓' : '<span class="text-gray-300">—</span>' !!}</td>
                                <td class="table-td" data-label="Active">{!! $fund->is_active ? '<span class="text-green-600">Active</span>' : '<span class="text-gray-400">Off</span>' !!}</td>
                                <td class="table-td text-right" data-label="Action">
                                    <div class="inline-flex gap-1">
                                        <button type="button" @click="edit = !edit" class="act-edit">Edit</button>
                                        <form method="POST" action="{{ route('accounting.funds.destroy', $fund) }}" data-confirm="Remove this fund?">@csrf @method('DELETE')<button class="act-del">Delete</button></form>
                                    </div>
                                    <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.funds.update', $fund) }}" class="mt-2 grid grid-cols-2 gap-2 text-left">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $fund->name }}" class="input" required>
                                        <input name="code" value="{{ $fund->code }}" class="input" required>
                                        <label class="flex items-center gap-1 text-xs"><input type="hidden" name="hospital_available" value="0"><input type="checkbox" name="hospital_available" value="1" class="rounded" @checked($fund->hospital_available)> Hospital-available</label>
                                        <label class="flex items-center gap-1 text-xs"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded" @checked($fund->is_active)> Active</label>
                                        <div class="col-span-2"><x-btn type="submit" class="w-full">Save</x-btn></div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No funds yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('accounting.funds.store') }}" class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-2 items-center border-t border-gray-100 dark:border-gray-700 pt-3">
                @csrf
                <input name="name" class="input sm:col-span-2" placeholder="Fund name" required>
                <input name="code" class="input" placeholder="Code (e.g. 101)" required>
                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="hospital_available" value="1" class="rounded"> Hospital</label>
                <div class="sm:col-span-4"><x-btn type="submit">Add fund</x-btn></div>
            </form>
        </x-card>

        {{-- ───────── Responsibility Centers ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Responsibility Centers <span class="text-gray-400 font-normal text-sm">(Office / Unit / Project)</span></h2>
            <p class="text-xs text-gray-400 mb-3">Used for the “Office/Unit/Project” dropdown on vouchers &amp; payroll. Each can carry its own code.</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($centers as $c)
                    <div class="flex items-center gap-2 py-2" x-data="{ edit: false }">
                        <div class="flex-1 min-w-0" x-show="!edit"><span class="font-medium text-sm">{{ $c->name }}</span> @if($c->code)<span class="text-xs text-gray-400">· {{ $c->code }}</span>@endif @unless($c->is_active)<span class="text-[10px] text-gray-400">(off)</span>@endunless</div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.centers.update', $c) }}" class="flex-1 flex gap-2">@csrf @method('PUT')
                            <input name="name" value="{{ $c->name }}" class="input" required>
                            <input name="code" value="{{ $c->code }}" class="input" placeholder="Code">
                            <input type="hidden" name="is_active" value="1">
                            <x-btn type="submit" class="shrink-0">Save</x-btn>
                        </form>
                        <button type="button" @click="edit=!edit" class="act-edit shrink-0">Edit</button>
                        <form method="POST" action="{{ route('accounting.centers.destroy', $c) }}" data-confirm="Remove?">@csrf @method('DELETE')<button class="act-del shrink-0">Delete</button></form>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">None yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('accounting.centers.store') }}" class="mt-3 flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">@csrf
                <input name="name" class="input" placeholder="Office / Unit / Project" required>
                <input name="code" class="input max-w-[140px]" placeholder="Code (optional)">
                <x-btn type="submit" class="shrink-0">Add</x-btn>
            </form>
        </x-card>

        {{-- ───────── Nature of Transaction ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Nature of Transaction</h2>
            <p class="text-xs text-gray-400 mb-3">Options for the “Nature of Transaction” dropdown.</p>
            <div class="flex flex-wrap gap-2">
                @forelse($natures as $n)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm">
                        {{ $n->name }}
                        <form method="POST" action="{{ route('accounting.natures.destroy', $n) }}" data-confirm="Remove “{{ $n->name }}”?">@csrf @method('DELETE')<button class="text-gray-400 hover:text-red-500">&times;</button></form>
                    </span>
                @empty
                    <p class="text-sm text-gray-400">None yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('accounting.natures.store') }}" class="mt-3 flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">@csrf
                <input name="name" class="input" placeholder="e.g. Payment" required>
                <x-btn type="submit" class="shrink-0">Add</x-btn>
            </form>
        </x-card>
    </div>
</x-app-layout>
