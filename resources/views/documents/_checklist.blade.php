{{-- Handover checklist. Parent form must provide Alpine `present` (array).
     Required count = main document + every attachment. --}}
<div class="rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
    <label class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer">
        <input type="checkbox" name="present[]" value="main" x-model="present" class="rounded text-[color:var(--color-primary)]">
        <span><span class="font-medium">Main document</span> <span class="text-xs text-gray-400">— {{ $document->document_type }}</span></span>
    </label>
    @foreach($document->attachments as $att)
        <label class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer">
            <input type="checkbox" name="present[]" value="att_{{ $att->id }}" x-model="present" class="rounded text-[color:var(--color-primary)]">
            <span>{{ $att->title }}
                <a href="{{ route('attachments.download', $att) }}" target="_blank" class="text-[11px] link ml-1" @click.stop>view</a>
            </span>
        </label>
    @endforeach
</div>
