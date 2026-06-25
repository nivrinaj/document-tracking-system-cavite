{{--
    Modern month-grid calendar.
    Expects: $events = [ 'Y-m-d' => [ ['text' => '...', 'bg' => '#..', 'fg' => '#..'], ... ] ]
             $year   = focus year (jumps to current month within it)
    Clicking a day's “+” dispatches a `cal-pick` window event with the date string,
    which the page's add-form date input listens for.
--}}
<div x-data="calGrid(@js($events), {{ $year }})" class="select-none">
    <div class="flex items-center justify-between mb-4">
        <button type="button" @click="prev()" class="h-8 w-8 grid place-items-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <h3 class="text-sm font-semibold tracking-wide" x-text="title"></h3>
        <button type="button" @click="next()" class="h-8 w-8 grid place-items-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <div class="grid grid-cols-7 gap-px bg-gray-200/70 dark:bg-gray-700 rounded-xl overflow-hidden ring-1 ring-gray-200/70 dark:ring-gray-700">
        <template x-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d">
            <div class="bg-gray-50 dark:bg-gray-800/80 py-2 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-400" x-text="d"></div>
        </template>
        <template x-for="cell in cells" :key="cell.key">
            <div class="bg-white dark:bg-gray-800 min-h-[88px] p-1.5"
                 :class="cell.inMonth ? '' : 'bg-gray-50/60 dark:bg-gray-800/40'">
                <div class="flex items-center justify-between">
                    <span class="grid place-items-center text-[11px] font-medium h-5 min-w-[1.25rem] px-1 rounded-full"
                          :class="cell.isToday ? 'text-white' : (cell.inMonth ? 'text-gray-500' : 'text-gray-300 dark:text-gray-600')"
                          :style="cell.isToday ? 'background: var(--color-primary)' : ''"
                          x-text="cell.day"></span>
                    <button type="button" x-show="cell.inMonth" @click="pick(cell.date)"
                            class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-[color:var(--color-primary)] text-base leading-none px-1"
                            style="opacity:1" title="Add on this day">+</button>
                </div>
                <div class="mt-1 space-y-0.5">
                    <template x-for="(e, i) in (events[cell.date] || [])" :key="i">
                        <div class="text-[10px] leading-tight px-1.5 py-0.5 rounded-md truncate font-medium"
                             :style="`background:${e.bg};color:${e.fg}`" x-text="e.text" :title="e.text"></div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        if (window.__calGridRegistered) return;
        window.__calGridRegistered = true;
        Alpine.data('calGrid', (events, year) => ({
            events: events || {},
            cur: new Date(year, (year === new Date().getFullYear() ? new Date().getMonth() : 0), 1),
            get title() { return this.cur.toLocaleString('en-US', { month: 'long', year: 'numeric' }); },
            prev() { this.cur = new Date(this.cur.getFullYear(), this.cur.getMonth() - 1, 1); },
            next() { this.cur = new Date(this.cur.getFullYear(), this.cur.getMonth() + 1, 1); },
            pick(date) { window.dispatchEvent(new CustomEvent('cal-pick', { detail: date })); },
            fmt(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; },
            get cells() {
                const y = this.cur.getFullYear(), m = this.cur.getMonth();
                const first = new Date(y, m, 1);
                const start = new Date(y, m, 1 - first.getDay());
                const today = this.fmt(new Date());
                const out = [];
                for (let i = 0; i < 42; i++) {
                    const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                    const ds = this.fmt(d);
                    out.push({ key: ds, date: ds, day: d.getDate(), inMonth: d.getMonth() === m, isToday: ds === today });
                }
                return out;
            },
        }));
    });
</script>
