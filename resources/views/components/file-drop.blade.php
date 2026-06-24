@props(['name', 'accept' => '', 'multiple' => false, 'capture' => false, 'label' => 'Choose a file', 'icon' => 'file'])

<label x-data="{ n: '' }"
       class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 cursor-pointer hover:border-[color:var(--color-primary)] hover:bg-[color:var(--color-primary)]/5 transition text-sm">
    @if($icon === 'camera')
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    @else
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    @endif
    <span class="min-w-0 flex-1 truncate text-gray-500 dark:text-gray-300" x-text="n || @js($label)"></span>
    <span x-show="n" @click.prevent="n=''; $refs.inp.value=''" class="text-gray-400 hover:text-red-500 text-xs">&times;</span>
    <input x-ref="inp" type="file" name="{{ $name }}" accept="{{ $accept }}" @if($multiple) multiple @endif @if($capture) capture="environment" @endif
           class="hidden" @change="n = [...$event.target.files].map(f => f.name).join(', ')">
</label>
