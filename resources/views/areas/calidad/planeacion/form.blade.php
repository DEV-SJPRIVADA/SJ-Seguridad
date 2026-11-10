<x-app-layout>
    @php
        $isEdit = $indicator !== null;
        $defaultFields = [
            ['key' => 'campo_1', 'label' => '', 'decimals' => 2, 'required' => true],
            ['key' => 'campo_2', 'label' => '', 'decimals' => 2, 'required' => true],
        ];
        $rawFields = old('fields', $version?->fields ?? $defaultFields);
        $initialFields = collect(is_array($rawFields) ? $rawFields : $defaultFields)
            ->values()
            ->map(function ($field, $index): array {
                $field = is_array($field) ? $field : [];

                return [
                    'key' => (string) ($field['key'] ?? 'campo_'.($index + 1)),
                    'label' => (string) ($field['label'] ?? ''),
                    'decimals' => in_array((int) ($field['decimals'] ?? 2), [0, 2], true) ? (int) ($field['decimals'] ?? 2) : 2,
                    'required' => (bool) ($field['required'] ?? true),
                ];
            })
            ->all();
        $createsDraftOnSave = $isEdit && $version && $version->isFrozen();
    @endphp

    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Constructor de indicador: identidad, medición, campos dinámicos y fórmula. --}}
    <div
        class="page-section planeacion-form-page"
        x-data="planeacionIndicatorForm(@js([
            'fields' => $initialFields,
            'formula' => old('formula', $version->formula ?? ''),
            'previewUrl' => $previewUrl,
        ]))"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="page-header-inner" style="padding-top: 0; margin-bottom: 1.25rem;">
                <h2 class="page-title">{{ $isEdit ? 'Editar indicador '.$indicator->code : 'Nuevo indicador' }}</h2>
                <p class="page-subtitle">
                    @if ($createsDraftOnSave)
                        Este indicador ya está publicado. Al guardar se crea una nueva versión en borrador y no se modifica la publicada.
                    @elseif ($isEdit)
                        Actualice la ficha. Mientras sea borrador se guarda sobre la misma versión.
                    @else
                        Defina los campos de captura y luego arme la fórmula con esas claves.
                    @endif
                </p>
            </div>

            <div class="req-form-layout">
                <div class="req-form-layout__main form-stack">
                    <form
                        id="planeacion-indicator-form"
                        method="POST"
                        action="{{ $isEdit ? route('calidad.planeacion.indicadores.update', $indicator) : route('calidad.planeacion.indicadores.store') }}"
                        class="form-stack"
                    >
                        @csrf
                        @if ($isEdit)
                            @method('PATCH')
                        @endif

                        <div class="req-form">
                            <div class="req-form__meta req-form__meta--edit">
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Tablero</span>
                                    <span class="req-form__meta-value">Planeación estratégica</span>
                                </div>
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Versión</span>
                                    <span class="req-form__meta-value">{{ $isEdit ? 'v'.$version->version_number.' · '.$version->statusLabel() : 'Borrador v1' }}</span>
                                </div>
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Área</span>
                                    <span class="req-form__meta-value">Calidad</span>
                                </div>
                            </div>

                            {{-- Identidad: el código lo escribe Calidad a mano y no cambia al versionar. --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">1</span>
                                    <div>
                                        <h4 class="req-form__section-title">Identidad</h4>
                                        <p class="req-form__section-desc">El código es único y se escribe a mano. El nombre, los insumos y la medición aparecen en la ficha.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="code" value="Código *" />
                                        <input id="code" name="code" type="text" class="form-input" maxlength="40" required value="{{ old('code', $indicator->code ?? '') }}" autocomplete="off">
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="name" value="Nombre *" />
                                        <input id="name" name="name" type="text" class="form-input" maxlength="255" required value="{{ old('name', $version->name ?? '') }}">
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="objective" value="Objetivo" />
                                        <textarea id="objective" name="objective" class="form-input" rows="3">{{ old('objective', $version->objective ?? '') }}</textarea>
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="insumos" value="Insumos para la medición" />
                                        <textarea id="insumos" name="insumos" class="form-input" rows="2" maxlength="2000">{{ old('insumos', $version->insumos ?? '') }}</textarea>
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="medicion" value="Medición" />
                                        <textarea id="medicion" name="medicion" class="form-input" rows="2" maxlength="2000">{{ old('medicion', $version->medicion ?? '') }}</textarea>
                                    </div>
                                </div>
                            </section>

                            {{-- Medición: catálogo de procesos de documentos de calidad; frecuencia V1 mensual. --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">2</span>
                                    <div>
                                        <h4 class="req-form__section-title">Medición</h4>
                                        <p class="req-form__section-desc">Proceso, unidad, meta y valor crítico. La frecuencia en esta fase es mensual.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--three">
                                    <div class="form-field">
                                        <x-input-label for="process_key" value="Proceso" />
                                        <x-searchable-select
                                            id="process_key"
                                            name="process_key"
                                            :options="$options['processes']"
                                            :value="old('process_key', $version->process_key ?? '')"
                                            placeholder="Seleccionar proceso"
                                            search-placeholder="Buscar proceso…"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="unit" value="Unidad *" />
                                        <x-searchable-select
                                            id="unit"
                                            name="unit"
                                            :options="$options['units']"
                                            :value="old('unit', $version->unit ?? 'percentage')"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="frequency" value="Frecuencia *" />
                                        <x-searchable-select
                                            id="frequency"
                                            name="frequency"
                                            :options="$options['frequencies']"
                                            :value="old('frequency', $version->frequency ?? 'monthly')"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="target_value" value="Meta *" />
                                        <input id="target_value" name="target_value" type="number" step="0.01" class="form-input" required value="{{ old('target_value', $version->target_value ?? '') }}">
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="target_operator" value="Operador de meta *" />
                                        <x-searchable-select
                                            id="target_operator"
                                            name="target_operator"
                                            :options="$options['operators']"
                                            :value="old('target_operator', $version->target_operator ?? '>=')"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="critical_value" value="Valor crítico" />
                                        <input id="critical_value" name="critical_value" type="number" step="0.01" class="form-input" value="{{ old('critical_value', $version->critical_value ?? '') }}">
                                    </div>
                                </div>
                            </section>

                            {{-- Campos de captura: 2 a 12 claves que luego usa la fórmula. --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">3</span>
                                    <div>
                                        <h4 class="req-form__section-title">Campos de captura</h4>
                                        <p class="req-form__section-desc">Entre 2 y 12 campos. La clave no se puede repetir y debe ir en minúsculas (letras, números y guion bajo).</p>
                                    </div>
                                </header>

                                <div class="planeacion-fields">
                                    <template x-for="(field, index) in fields" :key="index">
                                        <div class="planeacion-fields__row">
                                            <input type="hidden" :name="'fields[' + index + '][key]'" :value="field.key">
                                            <input type="hidden" :name="'fields[' + index + '][label]'" :value="field.label">
                                            <input type="hidden" :name="'fields[' + index + '][decimals]'" :value="field.decimals">
                                            <input type="hidden" :name="'fields[' + index + '][required]'" :value="field.required ? '1' : '0'">
                                            <input type="hidden" :name="'fields[' + index + '][sort]'" :value="index + 1">

                                            <div class="form-field">
                                                <label class="form-label" :for="'field_key_' + index">Clave *</label>
                                                <input
                                                    class="form-input"
                                                    :id="'field_key_' + index"
                                                    type="text"
                                                    x-model="field.key"
                                                    maxlength="40"
                                                    autocomplete="off"
                                                    @input="field.key = slugKey(field.key); syncSamples()"
                                                >
                                            </div>
                                            <div class="form-field planeacion-fields__label">
                                                <label class="form-label" :for="'field_label_' + index">Etiqueta *</label>
                                                <input class="form-input" :id="'field_label_' + index" type="text" x-model="field.label" maxlength="120">
                                            </div>
                                            <div class="form-field">
                                                <span class="form-label">Decimales</span>
                                                <div class="planeacion-fields__toggle" role="group" :aria-label="'Decimales del campo ' + (index + 1)">
                                                    <button type="button" class="module-tab" :class="field.decimals === 0 ? 'module-tab--active' : ''" @click="field.decimals = 0">0</button>
                                                    <button type="button" class="module-tab" :class="field.decimals === 2 ? 'module-tab--active' : ''" @click="field.decimals = 2">2</button>
                                                </div>
                                            </div>
                                            <div class="form-field planeacion-fields__required">
                                                <label class="form-label" :for="'field_required_' + index">Obligatorio</label>
                                                <input :id="'field_required_' + index" type="checkbox" x-model="field.required">
                                            </div>
                                            <div class="planeacion-fields__actions">
                                                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Subir" aria-label="Subir campo" @click="moveField(index, -1)" :disabled="index === 0">
                                                    <x-lucide-chevron-up width="18" height="18" aria-hidden="true" />
                                                </button>
                                                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Bajar" aria-label="Bajar campo" @click="moveField(index, 1)" :disabled="index === fields.length - 1">
                                                    <x-lucide-chevron-down width="18" height="18" aria-hidden="true" />
                                                </button>
                                                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--danger" title="Quitar campo" aria-label="Quitar campo" @click="removeField(index)" :disabled="fields.length <= 2">
                                                    <x-lucide-trash-2 width="18" height="18" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <button type="button" class="btn btn--secondary btn--sm" @click="addField()" :disabled="fields.length >= 12">
                                        Agregar campo
                                    </button>
                                    <p class="req-form__hint" x-show="fields.length >= 12" x-cloak>Ya alcanzó el máximo de 12 campos.</p>
                                </div>
                            </section>

                            {{-- Fórmula con chips de claves y prueba con valores de muestra. --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">4</span>
                                    <div>
                                        <h4 class="req-form__section-title">Fórmula</h4>
                                        <p class="req-form__section-desc">Use solo +, −, ×, ÷, paréntesis, números y las claves de los campos. Pulse una clave para insertarla.</p>
                                    </div>
                                </header>

                                <div class="planeacion-formula">
                                    <div class="planeacion-formula__chips" role="group" aria-label="Insertar clave en la fórmula">
                                        <template x-for="field in fields" :key="'chip-' + field.key">
                                            <button type="button" class="module-tab" @click="insertKey(field.key)" :disabled="! field.key" x-text="field.key || 'sin clave'"></button>
                                        </template>
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="formula" value="Expresión *" />
                                        <textarea id="formula" name="formula" class="form-input planeacion-formula__textarea" rows="3" x-model="formula" x-ref="formula" required>{{ old('formula', $version->formula ?? '') }}</textarea>
                                    </div>

                                    <div class="planeacion-formula__preview">
                                        <p class="form-label">Probar con valores de muestra</p>
                                        <div class="planeacion-formula__samples">
                                            <template x-for="field in fields" :key="'sample-' + field.key">
                                                <div class="form-field" x-show="field.key">
                                                    <label class="form-label" :for="'sample_' + field.key" x-text="field.label || field.key"></label>
                                                    <input class="form-input" type="number" step="any" :id="'sample_' + field.key" x-model="samples[field.key]">
                                                </div>
                                            </template>
                                        </div>
                                        <div class="planeacion-formula__preview-actions">
                                            <button type="button" class="btn btn--secondary btn--sm" @click="probar()">Probar</button>
                                            <p class="planeacion-formula__result" x-show="previewResult !== null" x-cloak>
                                                Resultado: <strong x-text="previewResult"></strong>
                                            </p>
                                            <p class="planeacion-formula__error" x-show="previewError" x-cloak x-text="previewError"></p>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>

                    <div class="req-form-actions">
                        <p class="req-form-actions__note">
                            Revise claves, fórmula y meta antes de guardar. Publicar congela la versión.
                        </p>
                        <div class="req-form-actions__group">
                            <a
                                href="{{ route('calidad.planeacion.indicadores') }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Cancelar"
                                aria-label="Cancelar"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </a>
                            @if ($isEdit && $canEdit)
                                @include('areas.calidad.planeacion.partials.share', [
                                    'indicator' => $indicator,
                                    'shareUsers' => $shareUsers,
                                    'shareShowModal' => false,
                                ])
                            @endif
                            <button
                                type="submit"
                                form="planeacion-indicator-form"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="{{ $isEdit ? 'Guardar cambios' : 'Crear indicador' }}"
                                aria-label="{{ $isEdit ? 'Guardar cambios' : 'Crear indicador' }}"
                            >
                                @if ($isEdit)
                                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                                @else
                                    <x-lucide-send width="18" height="18" aria-hidden="true" />
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de {{ $isEdit ? 'guardar' : 'crear' }}</h3>
                            <p class="panel-text">La fórmula solo puede usar las claves definidas arriba.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="req-form-guide__list">
                                <li class="req-form-guide__item">Ejemplo: <code>((campo_1 / campo_2) + campo_3) * 100</code>.</li>
                                <li class="req-form-guide__item">No se permiten funciones tipo SI() ni MIN().</li>
                                <li class="req-form-guide__item">Una división entre cero no deja guardar la captura.</li>
                                <li class="req-form-guide__item">Publicar congela la versión. La siguiente edición abre un borrador nuevo.</li>
                            </ul>
                        </div>
                    </div>

                    @if ($isEdit)
                        @include('areas.calidad.planeacion.partials.version-history', [
                            'versionHistory' => $versionHistory ?? [],
                            'activeVersionId' => $version?->id,
                            'consultQuery' => [
                                'indicator' => $indicator,
                            ],
                        ])
                    @endif
                </aside>
            </div>

            @if ($isEdit && $canEdit)
                @include('areas.calidad.planeacion.partials.share', [
                    'indicator' => $indicator,
                    'shareUsers' => $shareUsers,
                    'shareShowTrigger' => false,
                ])
            @endif
        </div>
    </div>

    <script>
        window.planeacionIndicatorForm = function (initial) {
            return {
                fields: initial.fields || [],
                formula: initial.formula || '',
                previewUrl: initial.previewUrl,
                samples: {},
                previewResult: null,
                previewError: '',
                init() {
                    this.syncSamples();
                },
                slugKey(value) {
                    return String(value || '')
                        .toLowerCase()
                        .replace(/[^a-z0-9_]/g, '_')
                        .replace(/^[^a-z]+/, '');
                },
                nextKey() {
                    let n = this.fields.length + 1;
                    let key = 'campo_' + n;
                    const used = new Set(this.fields.map((field) => field.key));
                    while (used.has(key)) {
                        n += 1;
                        key = 'campo_' + n;
                    }
                    return key;
                },
                addField() {
                    if (this.fields.length >= 12) {
                        return;
                    }
                    this.fields.push({ key: this.nextKey(), label: '', decimals: 2, required: true });
                    this.syncSamples();
                },
                removeField(index) {
                    if (this.fields.length <= 2) {
                        return;
                    }
                    this.fields.splice(index, 1);
                    this.syncSamples();
                },
                moveField(index, delta) {
                    const target = index + delta;
                    if (target < 0 || target >= this.fields.length) {
                        return;
                    }
                    const copy = this.fields.splice(index, 1)[0];
                    this.fields.splice(target, 0, copy);
                },
                insertKey(key) {
                    if (! key) {
                        return;
                    }
                    const el = this.$refs.formula;
                    const start = el.selectionStart ?? this.formula.length;
                    const end = el.selectionEnd ?? this.formula.length;
                    this.formula = this.formula.slice(0, start) + key + this.formula.slice(end);
                    this.$nextTick(() => {
                        el.focus();
                        const pos = start + key.length;
                        el.setSelectionRange(pos, pos);
                    });
                },
                syncSamples() {
                    const next = {};
                    this.fields.forEach((field) => {
                        if (! field.key) {
                            return;
                        }
                        next[field.key] = Object.prototype.hasOwnProperty.call(this.samples, field.key)
                            ? this.samples[field.key]
                            : '';
                    });
                    this.samples = next;
                },
                probar() {
                    this.previewResult = null;
                    this.previewError = '';
                    const values = {};
                    this.fields.forEach((field) => {
                        if (! field.key) {
                            return;
                        }
                        const raw = this.samples[field.key];
                        values[field.key] = raw === '' || raw === null ? 0 : Number(raw);
                    });
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    fetch(this.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ formula: this.formula, values }),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));
                            if (! response.ok) {
                                throw new Error(data.message || (data.errors && data.errors.formula && data.errors.formula[0]) || 'No se pudo evaluar la fórmula.');
                            }
                            return data;
                        })
                        .then((data) => {
                            const value = Number(data.result);
                            this.previewResult = Number.isFinite(value) ? value.toLocaleString('es-CO', { maximumFractionDigits: 4 }) : data.result;
                        })
                        .catch((error) => {
                            this.previewError = error.message || 'No se pudo evaluar la fórmula.';
                        });
                },
            };
        };
    </script>
</x-app-layout>
