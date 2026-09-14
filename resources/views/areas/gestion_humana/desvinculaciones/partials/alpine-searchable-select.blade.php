{{-- Markup reutilizable del searchable-select para filas Alpine (mismo Alpine.data). --}}
@php
    $selectId = $selectId ?? 'ss_'.uniqid();
@endphp
<div
    class="searchable-select-wrap"
    @click.outside="close()"
    @keydown.escape.stop="close()"
>
    <input type="hidden" :id="@js($selectId)" x-ref="hiddenInput" :value="value">
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
    <div
        type="button"
        class="searchable-select__trigger form-select"
        :class="{
            'searchable-select__trigger--open': open,
            'searchable-select__trigger--disabled': isDisabled,
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
        :aria-disabled="isDisabled.toString()"
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
                x-show="hasSelection && allowClear && !isDisabled"
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
    <div
        class="searchable-select__dropdown"
        x-show="open"
        x-cloak
        @keydown.tab="close()"
        @keydown.down.prevent="highlightNext()"
        @keydown.up.prevent="highlightPrev()"
        @keydown.enter.prevent="selectHighlighted()"
    >
        <div class="searchable-select__search-wrap">
            <input
                type="text"
                class="searchable-select__search-input"
                x-ref="searchInput"
                x-model="search"
                :placeholder="searchPlaceholder"
                autocomplete="off"
                spellcheck="false"
                @keydown.enter.stop.prevent="selectHighlighted()"
            >
        </div>
        <ul class="searchable-select__options" x-ref="optionsList" role="listbox">
            <template x-for="(opt, index) in filteredOptions" :key="String(opt.value) + '-' + index">
                <li
                    class="searchable-select__option"
                    :class="{
                        'searchable-select__option--highlighted': highlightedIndex === index,
                        'searchable-select__option--selected': String(value) === String(opt.value)
                    }"
                    @click="selectOption(opt)"
                    @mouseenter="highlightedIndex = index"
                    role="option"
                    :aria-selected="(String(value) === String(opt.value)).toString()"
                >
                    <span class="searchable-select__option-text" x-text="opt.label"></span>
                </li>
            </template>
            <li x-show="filteredOptions.length === 0" class="searchable-select__empty">Sin resultados</li>
        </ul>
    </div>
</div>
