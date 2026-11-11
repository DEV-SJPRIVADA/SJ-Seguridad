@php
    $isEdit = $dofa !== null;
    $itemsByQuadrant = $itemsByQuadrant ?? [];
    $oldItems = old('quadrants', []);
    $quadrantRows = [];
    foreach ($quadrants as $key => $label) {
        $rows = $oldItems[$key] ?? ($itemsByQuadrant[$key] ?? []);
        // Acepta tanto strings del formulario como modelos del DOFA cargado.
        $rows = collect(is_array($rows) ? $rows : [])
            ->map(fn ($row): string => is_string($row) ? $row : (string) ($row->description ?? ''))
            ->values()
            ->all();
        $quadrantRows[$key] = $rows === [] ? [''] : $rows;
    }
@endphp

{{-- Modal de alta/edición de DOFA: periodo libre y cuatro cuadrantes dinámicos. --}}
<x-modal name="planeacion-dofa-{{ $isEdit ? 'edit-'.$dofa->id : 'create' }}" maxWidth="3xl" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal planeacion-estrategia-modal"
        x-data="planeacionDofaForm(@js([
            'quadrants' => $quadrantRows,
        ]))"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-layout-grid width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">{{ $isEdit ? 'Editar DOFA' : 'Nuevo DOFA' }}</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        El periodo es libre: puede ser un mes, un trimestre o varios años.
                    </p>
                </div>
            </div>
            <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'planeacion-dofa-{{ $isEdit ? 'edit-'.$dofa->id : 'create' }}')">
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="planeacion-share-modal__body">
            <form
                method="POST"
                action="{{ $isEdit ? route('calidad.planeacion.estrategia.update', $dofa) : route('calidad.planeacion.estrategia.store') }}"
                class="planeacion-estrategia-form"
            >
                @csrf
                @if ($isEdit)
                    @method('PATCH')
                @endif

                <div class="form-grid form-grid--two">
                    <div class="form-field">
                        <x-input-label for="dofa_title_{{ $isEdit ? $dofa->id : 'new' }}" value="Título *" />
                        <input id="dofa_title_{{ $isEdit ? $dofa->id : 'new' }}" name="title" type="text" class="form-input" maxlength="160" required
                               value="{{ old('title', $dofa->title ?? '') }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_period_{{ $isEdit ? $dofa->id : 'new' }}" value="Periodo *" />
                        <input id="dofa_period_{{ $isEdit ? $dofa->id : 'new' }}" name="period_label" type="text" class="form-input" maxlength="80" required
                               placeholder="Ej.: 2026, T1 2026, 2026-2028"
                               value="{{ old('period_label', $dofa->period_label ?? '') }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_start_{{ $isEdit ? $dofa->id : 'new' }}" value="Desde *" />
                        <input id="dofa_start_{{ $isEdit ? $dofa->id : 'new' }}" name="starts_on" type="date" class="form-input" required
                               value="{{ old('starts_on', $dofa?->starts_on?->toDateString()) }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_end_{{ $isEdit ? $dofa->id : 'new' }}" value="Hasta *" />
                        <input id="dofa_end_{{ $isEdit ? $dofa->id : 'new' }}" name="ends_on" type="date" class="form-input" required
                               value="{{ old('ends_on', $dofa?->ends_on?->toDateString()) }}">
                    </div>
                    <div class="form-field req-form__field-span">
                        <x-input-label for="dofa_summary_{{ $isEdit ? $dofa->id : 'new' }}" value="Resumen" />
                        <textarea id="dofa_summary_{{ $isEdit ? $dofa->id : 'new' }}" name="summary" rows="2" maxlength="3000" class="form-input">{{ old('summary', $dofa->summary ?? '') }}</textarea>
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_status_{{ $isEdit ? $dofa->id : 'new' }}" value="Estado" />
                        <x-searchable-select
                            id="dofa_status_{{ $isEdit ? $dofa->id : 'new' }}"
                            name="status"
                            :options="[
                                ['value' => 'draft', 'label' => 'Borrador'],
                                ['value' => 'active', 'label' => 'Vigente'],
                            ]"
                            :value="old('status', $dofa->status ?? 'draft')"
                            :allow-clear="false"
                        />
                    </div>
                </div>

                <div class="planeacion-estrategia-quadrants">
                    @foreach ($quadrants as $key => $label)
                        <div class="planeacion-estrategia-quadrant planeacion-estrategia-quadrant--{{ $key }}">
                            <div class="planeacion-estrategia-quadrant__head">
                                <h4>{{ $label }}</h4>
                                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Agregar línea" aria-label="Agregar línea en {{ $label }}" @click="addRow('{{ $key }}')">
                                    <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                </button>
                            </div>
                            <template x-for="(row, index) in rows.{{ $key }}" :key="'{{ $key }}-' + index">
                                <div class="planeacion-estrategia-quadrant__row">
                                    <input type="text" class="form-input" maxlength="500"
                                           :name="'quadrants[{{ $key }}][' + index + ']'"
                                           x-model="rows.{{ $key }}[index]"
                                           placeholder="Escriba una línea">
                                    <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Quitar línea" aria-label="Quitar línea" @click="removeRow('{{ $key }}', index)">
                                        <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    @endforeach
                </div>

                <div class="ficha-empleados-masivos-modal__footer">
                    <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'planeacion-dofa-{{ $isEdit ? 'edit-'.$dofa->id : 'create' }}')">
                        <x-lucide-x width="16" height="16" aria-hidden="true" />
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn--primary">
                        <x-lucide-save width="16" height="16" aria-hidden="true" />
                        {{ $isEdit ? 'Guardar cambios' : 'Crear DOFA' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-modal>

@once
    <script>
        window.planeacionDofaForm = window.planeacionDofaForm || function (initial) {
            return {
                rows: initial.quadrants || { fortaleza: [''], oportunidad: [''], debilidad: [''], amenaza: [''] },
                addRow(quadrant) {
                    this.rows[quadrant].push('');
                },
                removeRow(quadrant, index) {
                    if (this.rows[quadrant].length <= 1) {
                        this.rows[quadrant] = [''];

                        return;
                    }
                    this.rows[quadrant].splice(index, 1);
                },
            };
        };
    </script>
@endonce
