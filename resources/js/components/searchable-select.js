export default function searchableSelect(config = {}) {
    return {
        open: false,
        search: '',
        value: config.value !== undefined && config.value !== null ? String(config.value) : '',
        selectedLabel: config.initialLabel ?? '',
        options: Array.isArray(config.options) ? config.options : [],
        placeholder: config.placeholder ?? 'Seleccionar…',
        searchPlaceholder: config.searchPlaceholder ?? 'Buscar…',
        required: Boolean(config.required),
        disabled: Boolean(config.disabled),
        allowClear: config.allowClear !== undefined ? Boolean(config.allowClear) : true,
        highlightedIndex: -1,

        init() {
            this.syncLabel();
            this.$watch('value', () => this.syncLabel());
        },

        syncLabel() {
            if (this.value === '' || this.value === null || this.value === undefined) {
                this.selectedLabel = '';
                return;
            }
            const found = this.options.find(opt => String(opt.value) === String(this.value));
            this.selectedLabel = found ? found.label : (this.selectedLabel || String(this.value));
        },

        get filteredOptions() {
            if (!this.search || !this.search.trim()) {
                return this.options;
            }
            const query = this.search.toLowerCase().trim();
            return this.options.filter(opt => {
                const label = String(opt.label || '').toLowerCase();
                const val = String(opt.value || '').toLowerCase();
                return label.includes(query) || val.includes(query);
            });
        },

        get hasSelection() {
            return this.value !== '' && this.value !== null && this.value !== undefined;
        },

        toggle() {
            if (this.disabled) return;
            if (this.open) {
                this.close();
            } else {
                this.openDropdown();
            }
        },

        openDropdown() {
            if (this.disabled) return;
            this.open = true;
            this.search = '';
            this.highlightedIndex = -1;
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus({ preventScroll: true });
                }
                this.scrollToSelected();
            });
        },

        close() {
            this.open = false;
            this.search = '';
            this.highlightedIndex = -1;
        },

        selectOption(opt) {
            this.value = String(opt.value);
            this.selectedLabel = opt.label;
            this.close();
            this.$dispatch('change', { value: this.value, label: this.selectedLabel });
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        clear() {
            if (this.disabled) return;
            this.value = '';
            this.selectedLabel = '';
            this.close();
            this.$dispatch('change', { value: '', label: '' });
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        highlightNext() {
            const list = this.filteredOptions;
            if (!list.length) return;
            this.highlightedIndex = (this.highlightedIndex + 1) % list.length;
            this.scrollHighlightedIntoView();
        },

        highlightPrev() {
            const list = this.filteredOptions;
            if (!list.length) return;
            this.highlightedIndex = (this.highlightedIndex - 1 + list.length) % list.length;
            this.scrollHighlightedIntoView();
        },

        selectHighlighted() {
            const list = this.filteredOptions;
            if (this.highlightedIndex >= 0 && this.highlightedIndex < list.length) {
                this.selectOption(list[this.highlightedIndex]);
            } else if (list.length === 1) {
                this.selectOption(list[0]);
            }
        },

        scrollHighlightedIntoView() {
            this.$nextTick(() => {
                const listEl = this.$refs.optionsList;
                if (!listEl) return;
                const activeEl = listEl.querySelector(`[data-index="${this.highlightedIndex}"]`);
                if (activeEl) {
                    activeEl.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        scrollToSelected() {
            this.$nextTick(() => {
                const listEl = this.$refs.optionsList;
                if (!listEl) return;
                const selectedEl = listEl.querySelector(`[data-selected="true"]`);
                if (selectedEl) {
                    selectedEl.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
}
