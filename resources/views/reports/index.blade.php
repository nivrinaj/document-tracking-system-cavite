<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <label class="label">Report</label>
            <select id="reportType" class="input sm:max-w-sm" onchange="document.querySelectorAll('[data-report]').forEach(el => el.classList.toggle('hidden', el.dataset.report !== this.value))">
                <option value="erecord">E-Record</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Choose a report; its filters appear below.</p>
        </div>

        {{-- ───────── E-Record ───────── --}}
        <div data-report="erecord">
            @if($canERecord)
                <x-card>
                    <form method="GET" action="{{ route('reports.erecord') }}" target="_blank" class="space-y-5">
                        <input type="hidden" name="format" value="pdf">
                        <div>
                            <label class="label">Report title</label>
                            <input type="text" name="title" value="{{ old('title', $defaultTitle) }}" class="input sm:max-w-sm" placeholder="E-Record">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Document Type <span class="text-red-500">*</span></label>
                                <select name="document_type" class="input" required>
                                    @forelse($eDocTypes as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @empty
                                        <option value="">— none available —</option>
                                    @endforelse
                                </select>
                            </div>
                            <div>
                                <label class="label">Fund <span class="text-red-500">*</span></label>
                                <select name="fund_id" class="input" required>
                                    <option value="">— Select fund —</option>
                                    @foreach($eFunds as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->reportCode() }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Month <span class="text-red-500">*</span></label>
                                <select name="month" class="input" required>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" @selected($m == now()->month)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Year <span class="text-red-500">*</span></label>
                                <select name="year" class="input" required>
                                    @foreach(range(now()->year, now()->year - 5) as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Day <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                                <select name="day" class="input">
                                    <option value="">All days</option>
                                    @foreach(range(1, 31) as $d)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <x-btn type="submit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Generate PDF (A4 landscape)
                            </x-btn>
                            <span class="text-xs text-gray-400">Opens in a new tab. All encoded documents matching the filters, any status.</span>
                        </div>
                    </form>
                </x-card>
            @else
                <x-card>
                    <p class="text-sm text-gray-500 dark:text-gray-400">The E-Record report is available to accounting offices. Ask a Super Admin to enable the “Voucher &amp; Payroll office” toggle for your office.</p>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
