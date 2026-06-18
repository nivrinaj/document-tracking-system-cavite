<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <div class="space-y-6">
        {{-- Quick numbers --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Documents" :value="$quick['total']" color="primary"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></x-slot:icon></x-stat-card>
            <x-stat-card label="Pending" :value="$quick['pending']" color="amber"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></x-slot:icon></x-stat-card>
            <x-stat-card label="Completed" :value="$quick['completed']" color="green"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></x-slot:icon></x-stat-card>
            <x-stat-card label="This Month" :value="$quick['this_month']" color="blue"><x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></x-slot:icon></x-stat-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Generator --}}
            <div class="lg:col-span-2">
                <x-card title="Generate a report">
                    <form method="GET" action="{{ route('reports.generate') }}" class="space-y-4">
                        <div>
                            <label class="label">Report type</label>
                            <select name="type" class="input" required>
                                @foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div><label class="label">Date from</label><input type="date" name="date_from" class="input"></div>
                            <div><label class="label">Date to</label><input type="date" name="date_to" class="input"></div>
                            <div>
                                <label class="label">Division</label>
                                <select name="division_id" class="input">
                                    <option value="">All divisions</option>
                                    @foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->code }} — {{ $d->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <x-btn type="submit" name="format" value="html">View Report</x-btn>
                            <x-btn type="submit" name="format" value="pdf" variant="secondary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download PDF
                            </x-btn>
                        </div>
                    </form>
                </x-card>
            </div>

            {{-- Recommended --}}
            <x-card title="Recommended reports">
                <ul class="space-y-2 text-sm">
                    @foreach($types as $key => $label)
                        <li class="flex gap-2">
                            <span class="text-[color:var(--color-primary)]">•</span>
                            <a href="{{ route('reports.generate', ['type' => $key, 'format' => 'html']) }}" class="hover:underline">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
</x-app-layout>
