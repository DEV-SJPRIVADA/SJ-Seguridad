<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => 'Matrices'])
    </x-slot>

    <div
        class="page-section matrices-page matrices-builder"
        x-data="matrixBuilder({
            fields: @js($fields),
            types: @js($fieldTypes),
            isPublished: @js($isPublished ?? false),
        })"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger bottom-spaced" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="matrices-alert-line">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <header class="matrices-builder__head">
                <div>
                    <p class="matrices-hero__eyebrow">{{ $matrix ? 'Editar definiciÃ³n' : 'Nueva matriz' }}</p>
                    <h1 class="matrices-hero__title">{{ $matrix?->name ?? 'DiseÃ±a tu matriz' }}</h1>
                    <p class="matrices-hero__text">
                        Arma el formulario con los campos que necesites. Los cambios se guardan como borrador
                        hasta que publiques la matriz.
                    </p>
                </div>

                <div class="matrices-builder__head-actions">
                    <a href="{{ $matrix ? route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) : route('matrices.index', ['module' => $module]) }}" class="btn btn--secondary">
                        Cancelar
                    </a>

                    @if ($matrix && ($matrix->isDraft() || $hasDraft))
                        <form method="POST" action="{{ route('matrices.publish', ['module' => $module, 'matrix' => $matrix->id]) }}" onsubmit="return confirm('Â¿Publicar la matriz? Los usuarios verÃ¡n esta versiÃ³n del formulario.');">
                            @csrf
                            <button type="submit" class="btn btn--info" @disabled($matrix->currentVersion === null && $matrix->isDraft())>
                                Publicar
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            <form
                method="POST"
                action="{{ $matrix ? route('matrices.update', ['module' => $module, 'matrix' => $matrix->id]) : route('matrices.store', ['module' => $module]) }}"
                class="matrices-builder__form"
            >
                @csrf
                @if ($matrix)
                    @method('PUT')
                @endif

                <input type="hidden" name="fields" :value="json">

                <div class="matrices-builder__grid">
                    <div class="matrices-builder__main">
                        <section class="panel">
                            <div class="panel__header panel__header--compact">
                                <h2 class="matrices-panel-title">Datos generales</h2>
                            </div>
                            <div class="panel__body">
                                <div class="matrices-form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="matrix-name">Nombre de la matriz</label>
                                        <input
                                            id="matrix-name"
                                            class="form-input"
                                            type="text"
                                            name="name"
                                            maxlength="160"
                                            required
                                            value="{{ old('name', $matrix?->name) }}"
                                            placeholder="Ej. Matriz de inspecciÃ³n de extintores"
                                        >
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="matrix-area">Ãrea dueÃ±a</label>
                                        <x-searchable-select
                                            id="matrix-area"
                                            name="area_key"
                                            :options="collect($areaOptions)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all()"
                                            :value="old('area_key', $defaultArea)"
                                            placeholder="Selecciona un Ã¡rea"
                                            searchPlaceholder="Buscar Ã¡reaâ€¦"
                                            :required="true"
                                        />
                                    </div>

                                    <div class="form-field matrices-form-grid__full">
                                        <label class="form-label" for="matrix-description">DescripciÃ³n</label>
                                        <textarea
                                            id="matrix-description"
                                            class="form-input"
                                            name="description"
                                            rows="3"
                                            maxlength="2000"
                                            placeholder="Para quÃ© sirve esta matriz y quiÃ©n debe diligenciarla"
                                        >{{ old('description', $matrix?->description) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="panel matrices-builder__fields">
                            <div class="panel__header matrices-builder__fields-head">
                                <div>
                                    <h2 class="matrices-panel-title">Campos del formulario</h2>
                                    <p class="matrices-builder__hint" x-text="`${fields.length} campo(s) en esta versiÃ³n`"></p>
                                </div>
                                <div class="matrices-builder__fields-tools">
                                    <button type="button" class="btn btn--secondary btn--sm" @click="expandAll()">Expandir</button>
                                    <button type="button" class="btn btn--secondary btn--sm" @click="collapseAll()">Contraer</button>
                                </div>
                            </div>

                            <div class="panel__body">
                                <div class="matrices-builder__add-row">
                                    <span class="matrices-builder__add-label">Agregar campo</span>
                                    <div class="matrices-type-picker matrices-type-picker--add">
                                        <template x-for="(label, type) in types" :key="type">
                                            <button type="button" class="matrices-type-btn" @click="addField(type)">
                                                <span x-text="label"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="matrices-builder__list">
                                    <template x-for="(field, index) in fields" :key="field._uid">
                                        <article class="matrices-field-card" :class="{ 'is-open': field._open, 'is-invalid': field.label.trim() === '' }">
                                            <header class="matrices-field-card__head">
                                                <div class="matrices-field-card__title" @click="toggleField(index)">
                                                    <span class="matrices-field-card__index" x-text="index + 1"></span>
                                                    <div class="matrices-field-card__name">
                                                        <span class="matrices-field-card__label" x-text="field.label || 'Campo sin nombre'"></span>
                                                        <span class="matrices-field-card__type" x-text="typeLabel(field.type)"></span>
                                                    </div>
                                                    <span class="matrices-field-card__req" x-show="field.required">Obligatorio</span>
                                                </div>

                                                <div class="matrices-field-card__actions">
                                                    <button type="button" class="matrices-icon-btn" title="Subir" @click="moveField(index, -1)" :disabled="index === 0">
                                                        <x-lucide-chevron-up width="16" height="16" />
                                                    </button>
                                                    <button type="button" class="matrices-icon-btn" title="Bajar" @click="moveField(index, 1)" :disabled="index === fields.length - 1">
                                                        <x-lucide-chevron-down width="16" height="16" />
                                                    </button>
                                                    <button type="button" class="matrices-icon-btn" title="Duplicar" @click="duplicateField(index)">
                                                        <x-lucide-copy width="16" height="16" />
                                                    </button>
                                                    <button type="button" class="matrices-icon-btn matrices-icon-btn--danger" title="Eliminar" @click="removeField(index)">
                                                        <x-lucide-trash-2 width="16" height="16" />
                                                    </button>
                                                    <button type="button" class="matrices-icon-btn" :title="field._open ? 'Contraer' : 'Editar'" @click="toggleField(index)">
                                                        <x-lucide-settings-2 width="16" height="16" />
                                                    </button>
                                                </div>
                                            </header>

                                            <div class="matrices-field-card__body" x-show="field._open" x-cloak>
                                                <div class="matrices-form-grid">
                                                    <div class="form-field">
                                                        <label class="form-label">Nombre del campo</label>
                                                        <input type="text" class="form-input" maxlength="160" x-model="field.label" placeholder="Ej. Fecha de inspecciÃ³n">
                                                    </div>

                                                    <div class="form-field">
                                                        <label class="form-label">SecciÃ³n</label>
                                                        <input type="text" class="form-input" maxlength="120" x-model="field.section" placeholder="Agrupa campos (opcional)">
                                                    </div>

                                                    <div class="form-field matrices-form-grid__full">
                                                        <label class="form-label">Tipo de campo</label>
                                                        <div class="matrices-type-picker">
                                                            <template x-for="(label, type) in types" :key="type">
                                                                <button
                                                                    type="button"
                                                                    class="matrices-type-btn"
                                                                    :class="{ 'is-active': field.type === type }"
                                                                    @click="changeType(index, type)"
                                                                    x-text="label"
                                                                ></button>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="form-field">
                                                        <label class="form-label">Texto de ayuda</label>
                                                        <input type="text" class="form-input" maxlength="255" x-model="field.help" placeholder="InstrucciÃ³n breve para quien diligencia">
                                                    </div>

                                                    <div class="form-field" x-show="field.type !== 'file' && field.type !== 'signature'">
                                                        <label class="form-label">Placeholder</label>
                                                        <input type="text" class="form-input" maxlength="160" x-model="field.placeholder" placeholder="Texto de ejemplo">
                                                    </div>

                                                    <div class="form-field">
                                                        <label class="form-label">Ancho</label>
                                                        <div class="matrices-segmented">
                                                            <template x-for="span in [4, 6, 12]" :key="span">
                                                                <button
                                                                    type="button"
                                                                    class="matrices-segmented__btn"
                                                                    :class="{ 'is-active': Number(field.col_span) === span }"
                                                                    :disabled="isFullWidth(field.type)"
                                                                    @click="field.col_span = span"
                                                                    x-text="span === 12 ? 'Completo' : `${span}/12`"
                                                                ></button>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="form-field matrices-form-grid__full">
                                                        <label class="matrices-check">
                                                            <input type="checkbox" x-model="field.required">
                                                            <span>Campo obligatorio</span>
                                                        </label>
                                                        <label class="matrices-check">
                                                            <input type="checkbox" x-model="field.show_in_table" :disabled="field.type === 'file' || field.type === 'signature'">
                                                            <span>Mostrar como columna en el listado (mÃ¡x. 6)</span>
                                                        </label>
                                                        <label class="matrices-check" x-show="isOptionType(field.type)">
                                                            <input type="checkbox" x-model="field.filterable">
                                                            <span>Permitir filtrar por este campo</span>
                                                        </label>
                                                        <label class="matrices-check" x-show="field.type === 'text' || field.type === 'textarea'">
                                                            <input type="checkbox" x-model="field.preserve_case">
                                                            <span>Conservar mayÃºsculas y minÃºsculas (no forzar MAYÃšSCULAS)</span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="matrices-options" x-show="isOptionType(field.type)">
                                                    <div class="matrices-options__head">
                                                        <span class="matrices-options__title">Opciones</span>
                                                        <button type="button" class="btn btn--secondary btn--sm" @click="addOption(index)">Agregar opciÃ³n</button>
                                                    </div>

                                                    <template x-for="(option, optionIndex) in field.options" :key="optionIndex">
                                                        <div class="matrices-options__row">
                                                            <span class="matrices-options__index" x-text="optionIndex + 1"></span>
                                                            <input type="text" class="form-input" maxlength="160" x-model="option.label" placeholder="Texto de la opciÃ³n">
                                                            <button type="button" class="matrices-icon-btn" title="Subir" @click="moveOption(index, optionIndex, -1)" :disabled="optionIndex === 0">
                                                                <x-lucide-chevron-up width="16" height="16" />
                                                            </button>
                                                            <button type="button" class="matrices-icon-btn" title="Bajar" @click="moveOption(index, optionIndex, 1)" :disabled="optionIndex === field.options.length - 1">
                                                                <x-lucide-chevron-down width="16" height="16" />
                                                            </button>
                                                            <button type="button" class="matrices-icon-btn matrices-icon-btn--danger" title="Quitar" @click="removeOption(index, optionIndex)" :disabled="field.options.length <= 1">
                                                                <x-lucide-x width="16" height="16" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </article>
                                    </template>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="matrices-builder__side">
                        <section class="panel matrices-builder__summary">
                            <div class="panel__header panel__header--compact">
                                <h2 class="matrices-panel-title">Resumen</h2>
                            </div>
                            <div class="panel__body">
                                <dl class="matrices-summary-list">
                                    <div>
                                        <dt>Campos</dt>
                                        <dd x-text="fields.length"></dd>
                                    </div>
                                    <div>
                                        <dt>Obligatorios</dt>
                                        <dd x-text="fields.filter((field) => field.required).length"></dd>
                                    </div>
                                    <div>
                                        <dt>En el listado</dt>
                                        <dd x-text="fields.filter((field) => field.show_in_table).length"></dd>
                                    </div>
                                </dl>

                                <ol class="matrices-summary-fields">
                                    <template x-for="(field, index) in fields" :key="field._uid">
                                        <li>
                                            <span class="matrices-summary-fields__index" x-text="index + 1"></span>
                                            <span class="matrices-summary-fields__label" x-text="field.label || 'Campo sin nombre'"></span>
                                            <span class="matrices-summary-fields__type" x-text="typeLabel(field.type)"></span>
                                        </li>
                                    </template>
                                </ol>

                                <div class="matrices-builder__save">
                                    <button type="submit" class="btn btn--primary matrices-btn-block">
                                        Guardar borrador
                                    </button>
                                    <p class="matrices-builder__save-note">
                                        Publicar congela esta versiÃ³n. Si editas una matriz publicada, se crea una
                                        versiÃ³n nueva sin afectar los registros existentes.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="panel matrices-builder__tips">
                            <div class="panel__body">
                                <h3 class="matrices-builder__tips-title">Buenas prÃ¡cticas</h3>
                                <ul class="matrices-builder__tips-list">
                                    <li>Usa nombres claros y sin abreviaturas.</li>
                                    <li>Marca solo los campos realmente obligatorios.</li>
                                    <li>Elige mÃ¡ximo 6 columnas para no saturar el listado.</li>
                                    <li>Adjuntos: 10 MB por archivo y 200 MB por matriz.</li>
                                </ul>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
