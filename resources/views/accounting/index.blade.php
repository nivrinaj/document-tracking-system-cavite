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
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($funds as $fund)
                    <div class="py-2" x-data="{ edit: false }">
                        <div class="flex items-center gap-2 flex-wrap" x-show="!edit">
                            <span class="font-medium text-sm">{{ $fund->name }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $fund->code }}</span>
                            @if($fund->report_code)<span class="text-xs text-gray-400 font-mono">· {{ $fund->report_code }}</span>@endif
                            @if($fund->hospital_available)<span class="text-[10px] px-1.5 py-0.5 rounded-full bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-300">Hospital</span>@endif
                            @if($fund->is_active)<span class="text-[10px] text-green-600 dark:text-green-400">Active</span>@else<span class="text-[10px] text-gray-400">Off</span>@endif
                            <div class="ml-auto inline-flex gap-1 shrink-0">
                                <button type="button" @click="edit = !edit" class="act-edit">Edit</button>
                                <form method="POST" action="{{ route('accounting.funds.destroy', $fund) }}" data-confirm="Remove this fund?">@csrf @method('DELETE')<button class="act-del">Delete</button></form>
                            </div>
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.funds.update', $fund) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-2 items-center">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $fund->name }}" class="input sm:col-span-2" placeholder="Name" required>
                            <input name="code" value="{{ $fund->code }}" class="input" placeholder="Code" required>
                            <input name="report_code" value="{{ $fund->report_code }}" class="input" placeholder="Report code (e.g. GF)">
                            <div class="flex items-center gap-3 text-xs shrink-0">
                                <label class="flex items-center gap-1 whitespace-nowrap"><input type="hidden" name="hospital_available" value="0"><input type="checkbox" name="hospital_available" value="1" class="rounded text-[color:var(--color-primary)]" @checked($fund->hospital_available)> Hospital</label>
                                <label class="flex items-center gap-1 whitespace-nowrap"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded text-[color:var(--color-primary)]" @checked($fund->is_active)> Active</label>
                            </div>
                            <x-btn type="submit" class="shrink-0">Save</x-btn>
                        </form>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">No funds yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('accounting.funds.store') }}" class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-2 items-center border-t border-gray-100 dark:border-gray-700 pt-3">
                @csrf
                <input name="name" class="input sm:col-span-2" placeholder="Fund name" required>
                <input name="code" class="input" placeholder="Code (e.g. 101)" required>
                <input name="report_code" class="input" placeholder="Report code (e.g. GF)">
                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="hospital_available" value="1" class="rounded text-[color:var(--color-primary)]"> Hospital</label>
                <div class="sm:col-span-5"><x-btn type="submit">Add fund</x-btn></div>
            </form>
        </x-card>

        {{-- ───────── Responsibility Centers: Offices / Units (with nested Projects) ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Responsibility Centers <span class="text-gray-400 font-normal text-sm">(Office / Unit)</span></h2>
            <p class="text-xs text-gray-400 mb-3">The first of two dropdowns on Vouchers &amp; Payroll. Expand an office/unit to manage its <strong>Projects</strong> — the second, dependent dropdown. Tick "Hospital RC" to move an entry to the separate Hospital list below instead (used by Hospital division encoders, no projects).</p>
            <div class="space-y-2">
                @forelse($centers as $c)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="{ edit: false, expanded: false }">
                        <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-700/30">
                            <button type="button" @click="expanded = !expanded" class="shrink-0 w-6 h-6 grid place-items-center rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-transform" :class="expanded && 'rotate-90'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <div class="flex-1 min-w-0" x-show="!edit">
                                <span class="font-medium text-sm">{{ $c->label() }}</span>
                                <span class="text-xs text-gray-400 ml-1">{{ $c->projects->count() }} {{ \Illuminate\Support\Str::plural('project', $c->projects->count()) }}</span>
                                @unless($c->is_active)<span class="text-[10px] text-gray-400 ml-1">(off)</span>@endunless
                            </div>
                            <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.centers.update', $c) }}" class="flex-1 flex gap-2">@csrf @method('PUT')
                                <input name="name" value="{{ $c->name }}" class="input" placeholder="Name" required>
                                <input name="code" value="{{ $c->code }}" class="input max-w-[120px]" placeholder="Code">
                                <input type="hidden" name="is_active" value="1">
                                <input type="hidden" name="is_hospital" value="0">
                                <label class="flex items-center gap-1 text-xs shrink-0 whitespace-nowrap"><input type="checkbox" name="is_hospital" value="1" class="rounded text-[color:var(--color-primary)]"> Hospital RC</label>
                                <x-btn type="submit" class="shrink-0">Save</x-btn>
                            </form>
                            <button type="button" @click="edit=!edit" class="act-edit shrink-0">Edit</button>
                            <form method="POST" action="{{ route('accounting.centers.destroy', $c) }}" data-confirm="Remove this office/unit and all its projects?">@csrf @method('DELETE')<button class="act-del shrink-0">Delete</button></form>
                        </div>
                        <div x-show="expanded" x-cloak class="px-3 py-2.5 pl-11 space-y-1.5 border-t border-gray-100 dark:border-gray-700">
                            @forelse($c->projects as $p)
                                <div class="flex items-center gap-2 py-1" x-data="{ editP: false }">
                                    <div class="flex-1 min-w-0 text-sm" x-show="!editP">{{ $p->code }} <span class="text-gray-400">—</span> {{ $p->name }} @unless($p->is_active)<span class="text-[10px] text-gray-400">(off)</span>@endunless</div>
                                    <form x-show="editP" x-cloak method="POST" action="{{ route('accounting.centers.projects.update', $p) }}" class="flex-1 flex gap-2">@csrf @method('PUT')
                                        <input name="code" value="{{ $p->code }}" class="input max-w-[100px]" placeholder="Code">
                                        <input name="name" value="{{ $p->name }}" class="input" placeholder="Project name" required>
                                        <input type="hidden" name="is_active" value="1">
                                        <x-btn type="submit" class="shrink-0">Save</x-btn>
                                    </form>
                                    <button type="button" @click="editP=!editP" class="act-edit shrink-0 text-xs">Edit</button>
                                    <form method="POST" action="{{ route('accounting.centers.projects.destroy', $p) }}" data-confirm="Remove this project?">@csrf @method('DELETE')<button class="act-del shrink-0 text-xs">Delete</button></form>
                                </div>
                            @empty
                                <p class="py-1 text-xs text-gray-400">No projects yet.</p>
                            @endforelse
                            <form method="POST" action="{{ route('accounting.centers.projects.store', $c) }}" class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">@csrf
                                <input name="code" class="input max-w-[100px]" placeholder="Code">
                                <input name="name" class="input" placeholder="New project name" required>
                                <x-btn type="submit" class="shrink-0">Add project</x-btn>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">None yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('accounting.centers.store') }}" class="mt-3 flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">@csrf
                <input name="name" class="input" placeholder="Office / Unit name" required>
                <input name="code" class="input max-w-[140px]" placeholder="Code (optional)">
                <x-btn type="submit" class="shrink-0">Add office/unit</x-btn>
            </form>
        </x-card>

        {{-- ───────── Hospital Responsibility Centers ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Hospital Responsibility Centers</h2>
            <p class="text-xs text-gray-400 mb-3">A separate flat list used only by encoders under a <strong>Hospital</strong> division — they see a single searchable dropdown sourced from this list, with no office/unit or project levels.</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($hospitalCenters as $c)
                    <div class="flex items-center gap-2 py-2" x-data="{ edit: false }">
                        <div class="flex-1 min-w-0" x-show="!edit"><span class="font-medium text-sm">{{ $c->label() }}</span> @unless($c->is_active)<span class="text-[10px] text-gray-400">(off)</span>@endunless</div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.centers.update', $c) }}" class="flex-1 flex gap-2">@csrf @method('PUT')
                            <input name="name" value="{{ $c->name }}" class="input" placeholder="Name" required>
                            <input name="code" value="{{ $c->code }}" class="input max-w-[120px]" placeholder="Code (optional)">
                            <input type="hidden" name="is_active" value="1">
                            <input type="hidden" name="is_hospital" value="1">
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
                <input type="hidden" name="is_hospital" value="1">
                <input name="name" class="input" placeholder="Hospital RC name" required>
                <input name="code" class="input max-w-[140px]" placeholder="Code (optional)">
                <x-btn type="submit" class="shrink-0">Add hospital RC</x-btn>
            </form>
        </x-card>

        {{-- ───────── Nature of Transaction ───────── --}}
        <x-card>
            <h2 class="font-semibold mb-1">Nature of Transaction</h2>
            <p class="text-xs text-gray-400 mb-3">Options for the “Nature of Transaction” dropdown. The <strong>report code</strong> is the short form shown on reports (e.g. Payt., Reimb.).</p>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($natures as $n)
                    <div class="flex items-center gap-2 py-2" x-data="{ edit: false }">
                        <div class="flex-1 min-w-0" x-show="!edit"><span class="font-medium text-sm">{{ $n->name }}</span> @if($n->report_code)<span class="text-xs text-gray-400 font-mono">· {{ $n->report_code }}</span>@endif @unless($n->is_active)<span class="text-[10px] text-gray-400">(off)</span>@endunless</div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('accounting.natures.update', $n) }}" class="flex-1 flex gap-2">@csrf @method('PUT')
                            <input name="name" value="{{ $n->name }}" class="input" required>
                            <input name="report_code" value="{{ $n->report_code }}" class="input max-w-[140px]" placeholder="Report code">
                            <input type="hidden" name="is_active" value="1">
                            <x-btn type="submit" class="shrink-0">Save</x-btn>
                        </form>
                        <button type="button" @click="edit=!edit" class="act-edit shrink-0">Edit</button>
                        <form method="POST" action="{{ route('accounting.natures.destroy', $n) }}" data-confirm="Remove “{{ $n->name }}”?">@csrf @method('DELETE')<button class="act-del shrink-0">Delete</button></form>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">None yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('accounting.natures.store') }}" class="mt-3 flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">@csrf
                <input name="name" class="input" placeholder="e.g. Payment" required>
                <input name="report_code" class="input max-w-[140px]" placeholder="Report code">
                <x-btn type="submit" class="shrink-0">Add</x-btn>
            </form>
        </x-card>
    </div>
</x-app-layout>
