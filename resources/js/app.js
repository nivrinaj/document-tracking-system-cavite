import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Reusable searchable multi-select (checkbox list + chips), used anywhere a
// "pick several of these" picker is needed — e.g. report office access,
// desktop-receive department scope. See <x-reports._multi-select /> for the markup.
document.addEventListener('alpine:init', () => {
    Alpine.data('multiSelect', ({ items, selected, name, placeholder, sync }) => ({
        items: items || [],
        selected: [...(selected || [])],
        name: name,
        placeholder: placeholder || '— Select —',
        open: false,
        search: '',
        init() {
            this.$watch('items', () => {
                const validIds = new Set(this.items.map(i => i.id));
                this.selected = this.selected.filter(s => validIds.has(s));
                if (sync) sync(this.selected);
            });
        },
        get filtered() {
            const q = this.search.toLowerCase();
            return this.items.filter(i => !q || i.label.toLowerCase().includes(q));
        },
        get selectedLabels() {
            return this.items.filter(i => this.selected.includes(i.id));
        },
        toggle(id) {
            const idx = this.selected.indexOf(id);
            if (idx >= 0) this.selected.splice(idx, 1);
            else this.selected.push(id);
            if (sync) sync(this.selected);
        },
        remove(id) {
            this.selected = this.selected.filter(v => v !== id);
            if (sync) sync(this.selected);
        },
        isSelected(id) { return this.selected.includes(id); },
    }));
});

Alpine.start();
