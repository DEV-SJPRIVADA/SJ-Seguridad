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
        formReadonly: false,
        openUp: false,

        // IDs usados para ubicar el dropdown teleotado a <body> (los $refs
        // cruzan el límite de x-teleport, así que se resuelve por ID).
        dropdownId: config.dropdownId ?? '',
        searchInputId: config.searchInputId ?? '',
        optionsListId: config.optionsListId ?? '',

        init() {
            // Instancias sin IDs explícitos (p. ej. filas Alpine con el partial alpine-searchable-select):
            // se generan únicos para poder ubicar el dropdown teleportado y posicionarlo junto al trigger.
            if (!this.dropdownId) {
                const uid = 'ss-' + Math.random().toString(36).slice(2, 10);
                this.dropdownId = `${uid}__dropdown`;
                this.searchInputId = this.searchInputId || `${uid}__search`;
                this.optionsListId = this.optionsListId || `${uid}__options`;
            }

            if (this.$refs.hiddenInput && this.value !== undefined && this.value !== null) {
                this.$refs.hiddenInput.value = String(this.value);
            }
            this.syncLabel();
            this.syncFormReadonly();
            this.$watch('value', (val) => {
                if (this.$refs.hiddenInput) {
                    this.$refs.hiddenInput.value = val !== undefined && val !== null ? String(val) : '';
                }
                this.syncLabel();
            });
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.$nextTick(() => this.positionDropdown());
                }
            });

            this._onReposition = () => this.scheduleReposition();
            window.addEventListener('scroll', this._onReposition, true);
            window.addEventListener('resize', this._onReposition);

            const form = this.$el.closest('.ficha-empleados-form');
            if (form) {
                this._formClassObserver = new MutationObserver(() => this.syncFormReadonly());
                this._formClassObserver.observe(form, { attributes: true, attributeFilter: ['class'] });
            }
        },

        destroy() {
            window.removeEventListener('scroll', this._onReposition, true);
            window.removeEventListener('resize', this._onReposition);
            if (this._formClassObserver) {
                this._formClassObserver.disconnect();
            }
        },

        /**
         * El dropdown vive teleportado en <body> con position: fixed, para que
         * los contenedores (modales, tablas, paneles con overflow) no lo
         * recorten. Se posiciona respecto al trigger con espacio suficiente
         * arriba o abajo y se re-alinea al hacer scroll o resize.
         */
        positionDropdown() {
            const dropdown = this.getDropdownEl();
            const trigger = this.$refs.trigger;
            if (!dropdown || !trigger || !this.open) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            if (rect.width === 0 && rect.height === 0) {
                return;
            }

            const width = Math.max(200, Math.min(rect.width, window.innerWidth - 16));
            let left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
            dropdown.style.width = `${width}px`;
            dropdown.style.left = `${left}px`;

            const height = dropdown.offsetHeight || 280;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            this.openUp = spaceBelow < height + 12 && spaceAbove > spaceBelow;

            let top;
            if (this.openUp) {
                top = Math.max(8, rect.top - height - 4);
            } else {
                top = rect.bottom + 4;
                // Si aún así desborda, se ancla al borde inferior disponible.
                if (top + height > window.innerHeight - 8) {
                    top = Math.max(8, window.innerHeight - 8 - height);
                }
            }
            dropdown.style.top = `${top}px`;

            dropdown.classList.add('searchable-select__dropdown--fixed');
            dropdown.classList.toggle('searchable-select__dropdown--up', this.openUp);
        },

        scheduleReposition() {
            if (this._repositionQueued || !this.open) {
                return;
            }
            this._repositionQueued = true;
            requestAnimationFrame(() => {
                this._repositionQueued = false;
                this.positionDropdown();
            });
        },

        getDropdownEl() {
            if (!this.dropdownId) {
                return null;
            }
            return document.getElementById(this.dropdownId);
        },

        getSearchInputEl() {
            if (!this.searchInputId) {
                return null;
            }
            return document.getElementById(this.searchInputId);
        },

        getOptionsListEl() {
            if (!this.optionsListId) {
                return null;
            }
            return document.getElementById(this.optionsListId);
        },

        resetDropdownPosition() {
            const dropdown = this.getDropdownEl();
            if (dropdown) {
                dropdown.classList.remove('searchable-select__dropdown--fixed');
                dropdown.classList.remove('searchable-select__dropdown--up');
                dropdown.style.removeProperty('top');
                dropdown.style.removeProperty('left');
                dropdown.style.removeProperty('width');
            }
            this.openUp = false;
        },

        syncFormReadonly() {
            const form = this.$el.closest('.ficha-empleados-form');
            this.formReadonly = Boolean(form?.classList.contains('ficha-empleados-form--readonly'));
            if (this.formReadonly) {
                this.close();
            }
        },

        syncLabel() {
            if (this.value === '' || this.value === null || this.value === undefined) {
                this.selectedLabel = '';
                return;
            }
            const found = this.options.find(opt => String(opt.value) === String(this.value));
            this.selectedLabel = found && String(found.value) !== '' ? found.label : '';
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

        get isDisabled() {
            return Boolean(this.disabled) || Boolean(this.formReadonly);
        },

        toggle() {
            if (this.isDisabled) return;
            if (this.open) {
                this.close();
            } else {
                this.openDropdown();
            }
        },

        openDropdown() {
            if (this.isDisabled) return;
            if (this._dropdownResetTimer) {
                clearTimeout(this._dropdownResetTimer);
                this._dropdownResetTimer = null;
            }
            this.open = true;
            this.search = '';
            this.highlightedIndex = -1;
            this.$nextTick(() => {
                this.positionDropdown();
                const searchInput = this.getSearchInputEl();
                if (searchInput) {
                    searchInput.focus({ preventScroll: true });
                }
                this.scrollToSelected();
            });
        },

        close() {
            if (!this.open) return;
            this.open = false;
            this.search = '';
            this.highlightedIndex = -1;
            // Se conserva el posicionamiento fijo durante la transición de
            // salida para evitar un "salto" visual del dropdown.
            if (this._dropdownResetTimer) {
                clearTimeout(this._dropdownResetTimer);
            }
            this._dropdownResetTimer = setTimeout(() => {
                this._dropdownResetTimer = null;
                if (!this.open) {
                    this.resetDropdownPosition();
                }
            }, 160);
        },

        focusSearchInput() {
            const searchInput = this.getSearchInputEl();
            if (searchInput) {
                searchInput.focus({ preventScroll: true });
            }
        },

        selectOption(opt) {
            const newVal = String(opt.value);
            const isCleared = newVal === '';
            this.value = newVal;
            this.selectedLabel = isCleared ? '' : opt.label;
            this.close();
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = this.value;
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            this.$dispatch('change', { value: this.value, label: this.selectedLabel });
        },

        clear() {
            if (this.isDisabled) return;
            this.value = '';
            this.selectedLabel = '';
            this.close();
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = '';
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            this.$dispatch('change', { value: '', label: '' });
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
            const listEl = this.getOptionsListEl();
            if (!listEl) {
                this.$nextTick(() => {
                    const list = this.getOptionsListEl();
                    if (!list) return;
                    const activeEl = list.querySelector(`[data-index="${this.highlightedIndex}"]`);
                    if (activeEl) {
                        activeEl.scrollIntoView({ block: 'nearest' });
                    }
                });
                return;
            }
            const activeEl = listEl.querySelector(`[data-index="${this.highlightedIndex}"]`);
            if (activeEl) {
                activeEl.scrollIntoView({ block: 'nearest' });
            }
        },

        scrollToSelected() {
            const listEl = this.getOptionsListEl();
            if (!listEl || this.value === '' || this.value === null || this.value === undefined) {
                return;
            }
            const selectedEl = listEl.querySelector('[data-selected="true"]');
            if (selectedEl) {
                selectedEl.scrollIntoView({ block: 'nearest' });
            }
        },
    };
}
