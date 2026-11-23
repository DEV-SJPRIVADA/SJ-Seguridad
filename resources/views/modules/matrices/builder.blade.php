<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => 'Matrices'])
    </x-slot>

    @php
        // Icono y descripción breve por tipo de campo: se usan en el selector y en la tarjeta de cada campo.
        $typeIcons = [
            'text' => 'type',
            'textarea' => 'align-left',
            'number' => 'hash',
            'currency' => 'circle-dollar-sign',
            'date' => 'calendar-days',
            'select' => 'list',
            'radio' => 'circle-dot',
            'checkbox' => 'check-square',
            'file' => 'paperclip',
            'signature' => 'pen-tool',
        ];

        $typeHints = [
            'text' => 'Línea corta',
            'textarea' => 'Texto largo',
            'number' => 'Cantidad',
            'currency' => 'Valor en dinero',
            'date' => 'Fecha',
            'select' => 'Lista desplegable',
            'radio' => 'Una sola opción',
            'checkbox' => 'Varias opciones',
            'file' => 'Adjuntar archivos',
            'signature' => 'Firma digital',
        ];

        $canPublish = $matrix !== null && ($matrix->isDraft() || $hasDraft);
    @endphp

    <div
        class="page-section matrices-page matrices-builder-page"
        x-data="matrixBuilder({
            fields: @js($fields),
            types: @js($fieldTypes),
            typeIcons: @js($typeIcons),
            matrixName: @js(old('name', $matrix?->name ?? '')),
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

            {{-- Cabecera: contexto, estado y acciones principales de la definición --}}
            <header class="matrices-builder__hero">
                <div class="matrices-builder__hero-copy">
                    <p class="matrices-builder__eyebrow">
                        <x-lucide-layout-grid width="14" height="14" aria-hidden="true" />
                        {{ $matrix ? 'Editar definición' : 'Nueva matriz' }}
                    </p>
                    <h1 class="matrices-builder__title">{{ $matrix?->name ?? 'Diseña tu matriz' }}</h1>
                    <p class="matrices-builder__subtitle">
                        Arma el formulario con los campos que necesites. Se guarda como borrador hasta que lo publiques.
                    </p>

                    <div class="matrices-builder__chips">
                        <span class="matrices-builder__chip">
                            <x-lucide-layers width="14" height="14" aria-hidden="true" />
                            <span x-text="`${fields.length} campo(s)`"></span>
                        </span>
                        @if ($matrix)
                            <span class="matrices-status matrices-status--{{ $matrix->status }}">{{ $matrix->statusLabel() }}</span>
                        @endif
                        <span class="matrices-builder__chip matrices-builder__chip--muted">
                            <x-lucide-save width="14" height="14" aria-hidden="true" />
                            {{ $matrix?->isPublished() ? 'Versión en edición' : 'Borrador' }}
                        </span>
                    </div>
                </div>

                <div class="matrices-builder__hero-actions">
                    <a href="{{ $matrix ? route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) : route('matrices.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                        Cancelar
                    </a>

                    @if ($canPublish)
                        <form method="POST" action="{{ route('matrices.publish', ['module' => $module, 'matrix' => $matrix->id]) }}" onsubmit="return confirm('¿Publicar la matriz? Los usuarios verán esta versión del formulario.');">
                            @csrf
                            <button type="submit" class="btn btn--info btn--sm" @disabled($matrix->currentVersion === null && $matrix->isDraft())>
                                <x-lucide-circle-check-big width="16" height="16" aria-hidden="true" />
                                Publicar
                            </button>
                        </form>
                    @endif

                    <button type="submit" form="matrix-builder-form" class="btn btn--primary btn--sm">
                        <x-lucide-save width="16" height="16" aria-hidden="true" />
                        Guardar borrador
                    </button>
                </div>
            </header>

            @if ($matrix && $matrix->isPublished())
                <div class="matrices-builder__notice" role="status">
                    <x-lucide-info width="18" height="18" aria-hidden="true" />
                    <p>
                        Esta matriz está publicada. Al guardar se crea una versión nueva y los registros existentes
                        se conservan intactos.
                    </p>
                </div>
            @endif

            <form
                id="matrix-builder-form"
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
                        {{-- Paso 1: identidad de la matriz --}}
                        <section class="panel matrices-builder__panel">
                            <div class="matrices-builder__panel-head">
                                <span class="matrices-builder__step" aria-hidden="true">1</span>
                                <div class="matrices-builder__panel-heading">
                                    <h2 class="matrices-builder__panel-title">Datos generales</h2>
                                    <p class="matrices-builder__panel-sub">Identifica la matriz y define el área dueña.</p>
                                </div>
                            </div>

                            <div class="matrices-builder__panel-body">
                                <div class="matrices-form-grid">
                                    <div class="form-field matrices-form-grid__col--6">
                                        <label class="form-label" for="matrix-name">
                                            Nombre de la matriz <span class="matrices-required">*</span>
                                        </label>
                                        <input
                                            id="matrix-name"
                                            class="form-input"
                                            type="text"
                                            name="name"
                                            maxlength="160"
                                            required
                                            value="{{ old('name', $matrix?->name) }}"
                                            x-model="matrixName"
                                            placeholder="Ej. Matriz de inspección de extintores"
                                        >
                                    </div>

                                    <div class="form-field matrices-form-grid__col--6">
                                        <label class="form-label" for="matrix-area">
                                            Área dueña <span class="matrices-required">*</span>
                                        </label>
                                        <x-searchable-select
                                            id="matrix-area"
                                            name="area_key"
                                            :options="collect($areaOptions)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all()"
                                            :value="old('area_key', $defaultArea)"
                                            placeholder="Selecciona un área"
                                            searchPlaceholder="Buscar área…"
                                            :required="true"
                                        />
                                    </div>

                                    <div class="form-field matrices-form-grid__col--12">
                                        <label class="form-label" for="matrix-description">Descripción</label>
                                        <textarea
                                            id="matrix-description"
                                            class="form-textarea"
                                            name="description"
                                            rows="3"
                                            maxlength="2000"
                                            placeholder="Para qué sirve esta matriz y quién debe diligenciarla"
                                        >{{ old('description', $matrix?->description) }}</textarea>
                                        <p class="matrices-help">Aparece en el listado para que otros usuarios sepan qué contiene.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Paso 2: constructor de campos --}}
                        <section class="panel matrices-builder__panel">
                            <div class="matrices-builder__panel-head">
                                <span class="matrices-builder__step" aria-hidden="true">2</span>
                                <div class="matrices-builder__panel-heading">
                                    <h2 class="matrices-builder__panel-title">Campos del formulario</h2>
                                    <p class="matrices-builder__panel-sub" x-text="`${fields.length} campo(s) en esta versión`"></p>
                                </div>
                                <div class="matrices-builder__panel-tools">
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Expandir todos los campos"
                                        aria-label="Expandir todos los campos"
                                        @click="expandAll()"
                                    >
                                        <x-lucide-chevrons-up width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Contraer todos los campos"
                                        aria-label="Contraer todos los campos"
                                        @click="collapseAll()"
                                    >
                                        <x-lucide-chevrons-down width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>

                            <div class="matrices-builder__panel-body">
                                {{-- Selector de tipo: cada tarjeta agrega un campo nuevo y lo abre para editarlo --}}
                                <div class="matrices-builder__add-row">
                                    <div class="matrices-builder__add-head">
                                        <span class="matrices-builder__add-label">Agregar campo</span>
                                        <span class="matrices-builder__add-hint">Elige el tipo y se abrirá listo para editar.</span>
                                    </div>
                                    <div class="matrices-type-grid">
                                        @foreach ($fieldTypes as $type => $label)
                                            <button type="button" class="matrices-type-card" @click="addField(@js($type))">
                                                <span class="matrices-type-card__icon" aria-hidden="true">
                                                    <x-dynamic-component :component="'lucide-'.($typeIcons[$type] ?? 'circle')" width="16" height="16" />
                                                </span>
                                                <span class="matrices-type-card__body">
                                                    <span class="matrices-type-card__label">{{ $label }}</span>
                                                    <span class="matrices-type-card__hint">{{ $typeHints[$type] ?? '' }}</span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="matrices-builder__list-head">
                                    <span class="matrices-builder__list-title">Estructura del formulario</span>
                                    <span class="matrices-builder__list-count" x-text="`${fields.length} campo(s)`"></span>
                                </div>

                                <div class="matrices-builder__list">
                                    <template x-for="(field, index) in fields" :key="field._uid">
                                        <article
                                            class="matrices-field-card"
                                            :class="{ 'is-open': field._open, 'is-invalid': field._touched && field.label.trim() === '' }"
                                            :id="`matrix-field-${index}`"
                                        >
                                            <header class="matrices-field-card__head">
                                                <div class="matrices-field-card__title" @click="toggleField(index)">
                                                    <span class="matrices-field-card__index" x-text="index + 1"></span>
                                                    <div class="matrices-field-card__name">
                                                        <span class="matrices-field-card__label" x-text="field.label || 'Campo sin nombre'"></span>
                                                        <span class="matrices-field-card__type" x-text="typeLabel(field.type)"></span>
                                                    </div>
                                                    <span class="matrices-field-card__warn" x-show="field._touched && field.label.trim() === ''">Falta el nombre</span>
                                                    <span class="matrices-field-card__req" x-show="field.required">Obligatorio</span>
                                                </div>

                                                <div class="matrices-field-card__actions">
                                                    <button type="button" class="cursos-catalogo-page__icon-btn" title="Subir" aria-label="Subir campo" @click="moveField(index, -1)" :disabled="index === 0">
                                                        <x-lucide-chevron-up width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <button type="button" class="cursos-catalogo-page__icon-btn" title="Bajar" aria-label="Bajar campo" @click="moveField(index, 1)" :disabled="index === fields.length - 1">
                                                        <x-lucide-chevron-down width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <button type="button" class="cursos-catalogo-page__icon-btn" title="Duplicar" aria-label="Duplicar campo" @click="duplicateField(index)">
                                                        <x-lucide-copy width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar campo" @click="removeField(index)">
                                                        <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="cursos-catalogo-page__icon-btn matrices-field-card__toggle"
                                                        :title="field._open ? 'Contraer' : 'Editar'"
                                                        :aria-label="field._open ? 'Contraer campo' : 'Editar campo'"
                                                        :class="{ 'is-open': field._open }"
                                                        @click="toggleField(index)"
                                                    >
                                                        <x-lucide-chevron-down width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                </div>
                                            </header>

                                            <div class="matrices-field-card__body" x-show="field._open" x-cloak>
                                                <div class="matrices-field-group">
                                                    <p class="matrices-field-group__title">
                                                        <x-lucide-type width="14" height="14" aria-hidden="true" />
                                                        Contenido
                                                    </p>

                                                    <div class="matrices-form-grid">
                                                        <div class="form-field matrices-form-grid__col--6">
                                                            <label class="form-label">Nombre del campo <span class="matrices-required">*</span></label>
                                                            <input type="text" class="form-input" maxlength="160" x-model="field.label" @input="field._touched = true" placeholder="Ej. Fecha de inspección">
                                                        </div>

                                                        <div class="form-field matrices-form-grid__col--6">
                                                            <label class="form-label">Sección</label>
                                                            <input type="text" class="form-input" maxlength="120" x-model="field.section" placeholder="Agrupa campos (opcional)">
                                                            <p class="matrices-help">Los campos con la misma sección se agrupan bajo un título.</p>
                                                        </div>

                                                        <div class="form-field matrices-form-grid__col--12">
                                                            <span class="form-label">Tipo de campo</span>
                                                            <div class="matrices-type-picker">
                                                                @foreach ($fieldTypes as $type => $label)
                                                                    <button
                                                                        type="button"
                                                                        class="matrices-type-btn"
                                                                        :class="{ 'is-active': field.type === @js($type) }"
                                                                        @click="changeType(index, @js($type))"
                                                                    >
                                                                        <span class="matrices-type-btn__icon" aria-hidden="true">
                                                                            <x-dynamic-component :component="'lucide-'.($typeIcons[$type] ?? 'circle')" width="14" height="14" />
                                                                        </span>
                                                                        <span>{{ $label }}</span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        </div>

                                                        <div class="form-field matrices-form-grid__col--6">
                                                            <label class="form-label">Texto de ayuda</label>
                                                            <input type="text" class="form-input" maxlength="255" x-model="field.help" placeholder="Instrucción breve para quien diligencia">
                                                        </div>

                                                        <div class="form-field matrices-form-grid__col--6" x-show="field.type !== 'file' && field.type !== 'signature'">
                                                            <label class="form-label">Placeholder</label>
                                                            <input type="text" class="form-input" maxlength="160" x-model="field.placeholder" placeholder="Texto de ejemplo">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="matrices-field-group">
                                                    <p class="matrices-field-group__title">
                                                        <x-lucide-sliders-horizontal width="14" height="14" aria-hidden="true" />
                                                        Presentación y reglas
                                                    </p>

                                                    <div class="matrices-form-grid">
                                                        <div class="form-field matrices-form-grid__col--4">
                                                            <span class="form-label">Ancho en el formulario</span>
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
                                                            <p class="matrices-help">Los tipos largos siempre ocupan el ancho completo.</p>
                                                        </div>

                                                        <div class="form-field matrices-form-grid__col--8">
                                                            <span class="form-label">Reglas del campo</span>
                                                            <div class="matrices-checks">
                                                                <label class="matrices-check">
                                                                    <input type="checkbox" x-model="field.required">
                                                                    <span>Campo obligatorio</span>
                                                                </label>
                                                                <label class="matrices-check">
                                                                    <input type="checkbox" x-model="field.show_in_table" :disabled="field.type === 'file' || field.type === 'signature'">
                                                                    <span>Mostrar como columna en el listado (máx. 6)</span>
                                                                </label>
                                                                <label class="matrices-check" x-show="isOptionType(field.type)">
                                                                    <input type="checkbox" x-model="field.filterable">
                                                                    <span>Permitir filtrar por este campo</span>
                                                                </label>
                                                                <label class="matrices-check" x-show="field.type === 'text' || field.type === 'textarea'">
                                                                    <input type="checkbox" x-model="field.preserve_case">
                                                                    <span>Conservar mayúsculas y minúsculas (no forzar MAYÚSCULAS)</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="matrices-options" x-show="isOptionType(field.type)" x-cloak>
                                                    <div class="matrices-options__head">
                                                        <div>
                                                            <span class="matrices-options__title">Opciones</span>
                                                            <p class="matrices-help">El orden de la lista es el orden en que se muestra.</p>
                                                        </div>
                                                        <button type="button" class="btn btn--secondary btn--sm" @click="addOption(index)">
                                                            <x-lucide-plus width="14" height="14" aria-hidden="true" />
                                                            Agregar opción
                                                        </button>
                                                    </div>

                                                    <template x-for="(option, optionIndex) in field.options" :key="optionIndex">
                                                        <div class="matrices-options__row">
                                                            <span class="matrices-options__index" x-text="optionIndex + 1"></span>
                                                            <input type="text" class="form-input" maxlength="160" x-model="option.label" placeholder="Texto de la opción">
                                                            <button type="button" class="cursos-catalogo-page__icon-btn" title="Subir" aria-label="Subir opción" @click="moveOption(index, optionIndex, -1)" :disabled="optionIndex === 0">
                                                                <x-lucide-chevron-up width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                            <button type="button" class="cursos-catalogo-page__icon-btn" title="Bajar" aria-label="Bajar opción" @click="moveOption(index, optionIndex, 1)" :disabled="optionIndex === field.options.length - 1">
                                                                <x-lucide-chevron-down width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                            <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Quitar" aria-label="Quitar opción" @click="removeOption(index, optionIndex)" :disabled="field.options.length <= 1">
                                                                <x-lucide-x width="16" height="16" aria-hidden="true" />
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
                        {{-- Resumen vivo: avance, métricas y acceso rápido a cada campo --}}
                        <div class="panel form-aside-panel matrices-builder__summary">
                            <div class="panel__header">
                                <div class="matrices-builder__summary-head">
                                    <span class="matrices-builder__aside-icon" aria-hidden="true">
                                        <x-lucide-list-checks width="16" height="16" />
                                    </span>
                                    <div>
                                        <h2 class="panel-title form-aside-guide__title">Resumen</h2>
                                        <p class="panel-text form-aside-guide__subtitle">Revisa el avance antes de guardar.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="panel__body">
                                <div class="matrices-builder__progress">
                                    <div class="matrices-builder__progress-head">
                                        <span>Preparación</span>
                                        <strong x-text="`${completionPercent}%`"></strong>
                                    </div>
                                    <div class="matrices-builder__progress-track" role="presentation">
                                        <span class="matrices-builder__progress-bar" :style="`width: ${completionPercent}%`"></span>
                                    </div>
                                </div>

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

                                <ul class="matrices-builder__checklist">
                                    <li :class="{ 'is-done': matrixName.trim() !== '' }">
                                        <span class="matrices-builder__check" aria-hidden="true">
                                            <x-lucide-check width="12" height="12" />
                                        </span>
                                        <span>Nombre de la matriz</span>
                                    </li>
                                    <li :class="{ 'is-done': fields.length > 0 && unnamedFields === 0 }">
                                        <span class="matrices-builder__check" aria-hidden="true">
                                            <x-lucide-check width="12" height="12" />
                                        </span>
                                        <span x-text="unnamedFields > 0 ? `${unnamedFields} campo(s) sin nombre` : 'Todos los campos tienen nombre'"></span>
                                    </li>
                                </ul>

                                <p class="matrices-builder__summary-caption" x-show="unnamedFields > 0" x-cloak>
                                    <x-lucide-triangle-alert width="14" height="14" aria-hidden="true" />
                                    Cada campo necesita un nombre para poder guardar.
                                </p>

                                <ol class="matrices-summary-fields">
                                    <template x-for="(field, index) in fields" :key="field._uid">
                                        <li>
                                            <button type="button" class="matrices-summary-fields__btn" @click="focusField(index)" title="Ir a este campo">
                                                <span class="matrices-summary-fields__index" x-text="index + 1"></span>
                                                <span class="matrices-summary-fields__label" :class="{ 'is-empty': field.label.trim() === '' }" x-text="field.label || 'Campo sin nombre'"></span>
                                                <span class="matrices-summary-fields__type" x-text="typeLabel(field.type)"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ol>

                                <div class="matrices-builder__save">
                                    <button type="submit" class="btn btn--primary matrices-btn-block">
                                        <x-lucide-save width="16" height="16" aria-hidden="true" />
                                        Guardar borrador
                                    </button>
                                    <p class="matrices-builder__save-note">
                                        Publicar congela esta versión. Si editas una matriz publicada, se crea una
                                        versión nueva sin afectar los registros existentes.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <x-form-aside-guide
                            class="matrices-builder__tips"
                            title="Buenas prácticas"
                            subtitle="Una matriz clara se diligencia mejor."
                            icon="lightbulb"
                            :items="[
                                'Usa nombres claros y sin abreviaturas.',
                                'Marca solo los campos realmente obligatorios.',
                                'Elige máximo 6 columnas para no saturar el listado.',
                                'Agrupa campos afines con la misma sección.',
                                'Adjuntos: 10 MB por archivo y 200 MB por matriz.',
                            ]"
                        />
                    </aside>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
