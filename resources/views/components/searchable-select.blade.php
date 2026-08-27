@props([
    'id' => null,
    'name' => '',
    'options' => [],
    'value' => '',
    'placeholder' => 'Seleccionar…',
    'searchPlaceholder' => 'Buscar…',
    'required' => false,
    'disabled' => false,
    'allowClear' => true,
])

@php
    $id = $id ?? 'select_'.uniqid();
    $selectedValue = (string) (old(str_replace(['[', ']'], ['.', ''], $name), $value) ?? '');

    // Normalizar opciones a lista de ['value' => string, 'label' => string]
    $normalizedOptions = [];
    foreach ($options as $key => $opt) {
        if (is_array($opt)) {
            $val = (string) ($opt['value'] ?? $opt['code'] ?? $opt['id'] ?? $key);
            $lbl = (string) ($opt['label'] ?? (isset($opt['code'], $opt['name']) ? "{$opt['code']} — {$opt['name']}" : ($opt['name'] ?? $opt['text'] ?? $val)));
            $normalizedOptions[] = ['value' => $val, 'label' => $lbl];
        } elseif (is_object($opt)) {
            $val = (string) ($opt->value ?? $opt->code ?? $opt->id ?? $key);
            $lbl = (string) ($opt->label ?? (isset($opt->code, $opt->name) ? "{$opt->code} — {$opt->name}" : ($opt->name ?? $opt->text ?? $val)));
            $normalizedOptions[] = ['value' => $val, 'label' => $lbl];
        } else {
            $normalizedOptions[] = ['value' => (string) $key, 'label' => (string) $opt];
        }
    }

    $initialLabel = '';
    foreach ($normalizedOptions as $opt) {
        if ((string) $opt['value'] === (string) $selectedValue) {
            $initialLabel = $opt['label'];
            break;
        }
    }
@endphp

<div
    {{ $attributes->merge(['class' => 'searchable-select-wrap']) }}
    x-data="searchableSelect({
        name: @js($name),
        value: @js($selectedValue),
        initialLabel: @js($initialLabel),
        options: @js($normalizedOptions),
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        required: @js($required),
        disabled: @js($disabled),
        allowClear: @js($allowClear),
    })"
    @click.outside="close()"
    @keydown.escape.stop="close()"
>
    {{-- Campo oculto para envío nativo de formulario --}}
    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $id }}"
        :value="value"
        :disabled="disabled"
        x-ref="hiddenInput"
    >
    <template x-if="required">
        <input
            type="text"
            tabindex="-1"
            aria-hidden="true"
            style="position: absolute; opacity: 0; width: 0; height: 0; padding: 0; margin: 0; border: 0; pointer-events: none;"
            :required="required && !hasSelection"
            :value="value"
        >
    </template>

    {{-- Botón de activación (trigger) --}}
    <div
        type="button"
        class="searchable-select__trigger form-select"
        :class="{
            'searchable-select__trigger--open': open,
            'searchable-select__trigger--disabled': disabled,
            'searchable-select__trigger--has-value': hasSelection
        }"
        @click="toggle()"
        tabindex="0"
        @keydown.space.prevent="toggle()"
        @keydown.enter.prevent="toggle()"
        @keydown.down.prevent="openDropdown()"
        @keydown.up.prevent="openDropdown()"
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        :aria-disabled="disabled.toString()"
    >
        <span
            class="searchable-select__label"
            :class="{ 'searchable-select__label--placeholder': !hasSelection }"
            x-text="hasSelection ? selectedLabel : placeholder"
        ></span>

        <div class="searchable-select__actions" @click.stop>
            <button
                type="button"
                class="searchable-select__clear"
                x-show="hasSelection && allowClear && !disabled"
                @click.stop="clear()"
                title="Limpiar selección"
                tabindex="-1"
                aria-label="Limpiar selección"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="searchable-select__clear-icon">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Panel Desplegable (Dropdown) --}}
    <div
        class="searchable-select__dropdown"
        x-show="open"
        x-cloak
        x-transition:enter="searchable-select-enter"
        x-transition:enter-start="searchable-select-enter-start"
        x-transition:enter-end="searchable-select-enter-end"
        x-transition:leave="searchable-select-leave"
        x-transition:leave-start="searchable-select-leave-start"
        x-transition:leave-end="searchable-select-leave-end"
        @keydown.tab="close()"
        @keydown.down.prevent="highlightNext()"
        @keydown.up.prevent="highlightPrev()"
        @keydown.enter.prevent="selectHighlighted()"
    >
        {{-- Buscador integrado --}}
        <div class="searchable-select__search-wrap">
            <svg class="searchable-select__search-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
            <input
                type="text"
                class="searchable-select__search-input"
                x-ref="searchInput"
                x-model="search"
                :placeholder="searchPlaceholder"
                autocomplete="off"
                spellcheck="false"
            >
        </div>

        {{-- Lista de Opciones --}}
        <ul
            class="searchable-select__options"
            x-ref="optionsList"
            role="listbox"
        >
            <template x-for="(opt, index) in filteredOptions" :key="opt.value">
                <li
                    class="searchable-select__option"
                    :class="{
                        'searchable-select__option--highlighted': highlightedIndex === index,
                        'searchable-select__option--selected': String(value) === String(opt.value)
                    }"
                    :data-index="index"
                    :data-selected="(String(value) === String(opt.value)).toString()"
                    @click="selectOption(opt)"
                    @mouseenter="highlightedIndex = index"
                    role="option"
                    :aria-selected="(String(value) === String(opt.value)).toString()"
                >
                    <span class="searchable-select__option-text" x-text="opt.label"></span>
                    <svg
                        x-show="String(value) === String(opt.value)"
                        class="searchable-select__check-icon"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </li>
            </template>

            <li
                x-show="filteredOptions.length === 0"
                class="searchable-select__empty"
            >
                Sin resultados
            </li>
        </ul>
    </div>
</div>
