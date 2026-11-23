/**
 * Constructor visual de matrices dinámicas.
 *
 * El servidor solo recibe el JSON final en el input `fields`; toda la
 * edición (agregar, ordenar, opciones y reglas por campo) ocurre aquí.
 * Las claves `field_key` las emite el backend al guardar, por eso los campos
 * nuevos llegan sin clave y se conservan las existentes al editar.
 */
const OPTION_TYPES = ['select', 'radio', 'checkbox'];
const FULL_WIDTH_TYPES = ['textarea', 'file', 'signature'];

let keySeed = 0;

function nextKey() {
    keySeed += 1;

    return `draft_${Date.now()}_${keySeed}`;
}

function normalizeOption(option) {
    if (typeof option === 'string') {
        return { key: '', label: option };
    }

    return {
        key: option && option.key ? String(option.key) : '',
        label: option && option.label ? String(option.label) : '',
    };
}

function normalizeField(field) {
    return {
        field_key: field.field_key || '',
        label: field.label || '',
        type: field.type || 'text',
        section: field.section || '',
        help: field.help || '',
        placeholder: field.placeholder || '',
        required: Boolean(field.required),
        col_span: Number(field.col_span) || 12,
        show_in_table: Boolean(field.show_in_table),
        filterable: Boolean(field.filterable),
        preserve_case: Boolean(field.preserve_case),
        options: Array.isArray(field.options) ? field.options.map(normalizeOption) : [],
        _uid: nextKey(),
        _open: false,
        _touched: false,
    };
}

export default function matrixBuilder(config = {}) {
    return {
        fields: (config.fields || []).map(normalizeField),
        types: config.types || {},
        typeIcons: config.typeIcons || {},
        matrixName: config.matrixName || '',
        isPublished: Boolean(config.isPublished),
        openAll: false,

        init() {
            if (this.fields.length === 0) {
                this.addField('text');
            }
        },

        get hasOptionFields() {
            return this.fields.some((field) => this.isOptionType(field.type));
        },

        /** Campos con nombre definido (el resto bloquea el guardado). */
        get namedFields() {
            return this.fields.filter((field) => String(field.label || '').trim() !== '').length;
        },

        get unnamedFields() {
            return this.fields.length - this.namedFields;
        },

        /** Avance del borrador: nombre de la matriz + un punto por cada campo nombrado. */
        get completionPercent() {
            const total = this.fields.length + 1;
            const done = this.namedFields + (String(this.matrixName || '').trim() !== '' ? 1 : 0);

            return total === 0 ? 0 : Math.round((done * 100) / total);
        },

        /** Abre el campo desde el resumen y lo lleva a la vista. */
        focusField(index) {
            const field = this.fields[index];

            if (! field) {
                return;
            }

            field._open = true;

            const element = document.getElementById(`matrix-field-${index}`);

            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        get json() {
            return JSON.stringify(this.fields.map((field) => ({
                field_key: field.field_key,
                label: field.label,
                type: field.type,
                section: field.section,
                help: field.help,
                placeholder: field.placeholder,
                required: field.required,
                col_span: field.col_span,
                show_in_table: field.show_in_table,
                filterable: field.filterable,
                preserve_case: field.preserve_case,
                options: field.options
                    .filter((option) => String(option.label || '').trim() !== '')
                    .map((option) => ({
                        key: option.key,
                        label: option.label.trim(),
                    })),
            })));
        },

        isOptionType(type) {
            return OPTION_TYPES.includes(type);
        },

        isFullWidth(type) {
            return FULL_WIDTH_TYPES.includes(type);
        },

        typeLabel(type) {
            return this.types[type] || type;
        },

        addField(type = 'text') {
            this.fields.push(normalizeField({
                type,
                col_span: this.isFullWidth(type) ? 12 : 6,
                options: this.isOptionType(type) ? [{ key: '', label: '' }] : [],
                _open: true,
            }));

            this.fields[this.fields.length - 1]._open = true;
        },

        duplicateField(index) {
            const source = this.fields[index];

            if (! source) {
                return;
            }

            const copy = normalizeField({ ...source, field_key: '' });
            copy.label = `${source.label} (copia)`;
            copy._open = true;

            this.fields.splice(index + 1, 0, copy);
        },

        removeField(index) {
            if (this.fields.length <= 1) {
                this.fields[index].label = '';
                this.fields[index]._open = true;

                return;
            }

            this.fields.splice(index, 1);
        },

        moveField(index, direction) {
            const target = index + direction;

            if (target < 0 || target >= this.fields.length) {
                return;
            }

            const [field] = this.fields.splice(index, 1);
            this.fields.splice(target, 0, field);
        },

        toggleField(index) {
            this.fields[index]._open = ! this.fields[index]._open;
        },

        collapseAll() {
            this.openAll = false;
            this.fields.forEach((field) => {
                field._open = false;
            });
        },

        expandAll() {
            this.openAll = true;
            this.fields.forEach((field) => {
                field._open = true;
            });
        },

        changeType(index, type) {
            const field = this.fields[index];

            if (! field) {
                return;
            }

            field.type = type;

            if (this.isOptionType(type) && field.options.length === 0) {
                field.options.push({ key: '', label: '' });
            }

            if (this.isFullWidth(type)) {
                field.col_span = 12;
            }

            if (type === 'file' || type === 'signature') {
                field.placeholder = '';
                field.show_in_table = false;
            }
        },

        addOption(index) {
            this.fields[index]?.options.push({ key: '', label: '' });
        },

        removeOption(index, optionIndex) {
            const field = this.fields[index];

            if (! field || field.options.length <= 1) {
                return;
            }

            field.options.splice(optionIndex, 1);
        },

        moveOption(index, optionIndex, direction) {
            const field = this.fields[index];
            const target = optionIndex + direction;

            if (! field || target < 0 || target >= field.options.length) {
                return;
            }

            const [option] = field.options.splice(optionIndex, 1);
            field.options.splice(target, 0, option);
        },
    };
}
