@props(['isHospital' => false, 'officeOptions' => [], 'projectsByOffice' => [], 'hospitalOptions' => []])

{{-- Responsibility Center picker. Hospital-division encoders see one searchable dropdown
     (hospitalOptions). Everyone else sees two dependent searchable dropdowns: Office/Unit,
     then Project (filtered by the selected office). Relies on the parent form's `acct`
     Alpine property (not redeclared here) to drive the required state. --}}
<div x-data="{
        isHospital: @js($isHospital),
        hOpen: false, hQ: '', hVal: '{{ old('responsibility_center_id') }}', hLabel: '',
        hOpts: @js($hospitalOptions),
        offOpen: false, offQ: '', offVal: '{{ $isHospital ? '' : old('responsibility_center_id') }}', offLabel: '',
        offOpts: @js($officeOptions),
        projOpen: false, projQ: '', projVal: '{{ old('responsibility_center_project_id') }}', projLabel: '',
        projByOffice: @js($projectsByOffice),
        get projOpts() { return this.projByOffice[this.offVal] || []; },
        get hFiltered() { const q = this.hQ.toLowerCase().trim(); return this.hOpts.filter(o => !q || o.label.toLowerCase().includes(q)); },
        get offFiltered() { const q = this.offQ.toLowerCase().trim(); return this.offOpts.filter(o => !q || o.label.toLowerCase().includes(q)); },
        get projFiltered() { const q = this.projQ.toLowerCase().trim(); return this.projOpts.filter(o => !q || o.label.toLowerCase().includes(q)); },
        pickOffice(o) { this.offVal = String(o.value); this.offLabel = o.label; this.offOpen = false; this.projVal = ''; this.projLabel = ''; },
        init() {
            if (this.offVal) { const f = this.offOpts.find(o => String(o.value) === String(this.offVal)); if (f) this.offLabel = f.label; }
            if (this.hVal) { const f = this.hOpts.find(o => String(o.value) === String(this.hVal)); if (f) this.hLabel = f.label; }
            if (this.projVal) { this.$nextTick(() => { const f = this.projOpts.find(o => String(o.value) === String(this.projVal)); if (f) this.projLabel = f.label; }); }
        },
     }" @click.outside="hOpen = false; offOpen = false; projOpen = false">

    <input type="hidden" name="responsibility_center_id" :value="isHospital ? hVal : offVal">
    <input type="hidden" name="responsibility_center_project_id" :value="isHospital ? '' : projVal">

    {{-- Hospital: single searchable dropdown --}}
    <template x-if="isHospital">
        <div>
            <label class="label">Responsibility Center <span class="text-red-500">*</span></label>
            <div class="relative">
                <button type="button" @click="hOpen = !hOpen; hQ = ''; $nextTick(() => hOpen && $refs.hq && $refs.hq.focus())"
                        class="input-btn flex items-center justify-between gap-2 text-left" x-bind:required="acct">
                    <span class="truncate" :class="!hVal ? 'text-gray-400' : ''" x-text="hVal ? hLabel : '— Select hospital RC —'"></span>
                    <span class="flex items-center gap-1 shrink-0">
                        <button type="button" x-show="hVal" x-cloak @click.stop="hVal = ''; hLabel = ''"
                                class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div x-show="hOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                    <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                        <input x-ref="hq" type="text" x-model="hQ" @click.stop class="input py-1.5 text-sm" placeholder="Search…">
                    </div>
                    <div class="max-h-56 overflow-y-auto py-1 text-sm">
                        <template x-for="o in hFiltered" :key="o.value">
                            <button type="button" @click="hVal = String(o.value); hLabel = o.label; hOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="o.label"></button>
                        </template>
                        <p x-show="!hFiltered.length" class="px-3 py-2 text-gray-400">No options available.</p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Non-hospital: two dependent searchable dropdowns --}}
    <template x-if="!isHospital">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Resp. Center — Office/Unit <span class="text-red-500">*</span></label>
                <div class="relative">
                    <button type="button" @click="offOpen = !offOpen; offQ = ''; $nextTick(() => offOpen && $refs.offq && $refs.offq.focus())"
                            class="input-btn flex items-center justify-between gap-2 text-left" x-bind:required="acct">
                        <span class="truncate" :class="!offVal ? 'text-gray-400' : ''" x-text="offVal ? offLabel : '— Select office/unit —'"></span>
                        <span class="flex items-center gap-1 shrink-0">
                            <button type="button" x-show="offVal" x-cloak @click.stop="offVal = ''; offLabel = ''; projVal = ''; projLabel = ''"
                                    class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </button>
                    <div x-show="offOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                            <input x-ref="offq" type="text" x-model="offQ" @click.stop class="input py-1.5 text-sm" placeholder="Search…">
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1 text-sm">
                            <template x-for="o in offFiltered" :key="o.value">
                                <button type="button" @click="pickOffice(o)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="o.label"></button>
                            </template>
                            <p x-show="!offFiltered.length" class="px-3 py-2 text-gray-400">No options available.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="label">Resp. Center — Project <span class="text-red-500">*</span></label>
                <div class="relative">
                    <button type="button" @click="offVal && (projOpen = !projOpen); projQ = ''; $nextTick(() => projOpen && $refs.projq && $refs.projq.focus())"
                            class="input-btn flex items-center justify-between gap-2 text-left" :class="!offVal ? 'opacity-50 cursor-not-allowed' : ''"
                            x-bind:required="acct" :disabled="!offVal">
                        <span class="truncate" :class="!projVal ? 'text-gray-400' : ''" x-text="!offVal ? 'Select office/unit first' : (projVal ? projLabel : '— Select project —')"></span>
                        <span class="flex items-center gap-1 shrink-0">
                            <button type="button" x-show="projVal" x-cloak @click.stop="projVal = ''; projLabel = ''"
                                    class="w-4 h-4 grid place-items-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </button>
                    <div x-show="projOpen" x-cloak x-transition.opacity class="absolute z-30 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                            <input x-ref="projq" type="text" x-model="projQ" @click.stop class="input py-1.5 text-sm" placeholder="Search…">
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1 text-sm">
                            <template x-for="o in projFiltered" :key="o.value">
                                <button type="button" @click="projVal = String(o.value); projLabel = o.label; projOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-text="o.label"></button>
                            </template>
                            <p x-show="!projFiltered.length" class="px-3 py-2 text-gray-400">No projects under this office/unit yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
