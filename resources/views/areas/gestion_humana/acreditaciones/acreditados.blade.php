<x-app-layout>
    @php
        $showNuevoModal = $canEdit && $errors->any() && ! $errors->has('import_file');
        $showMasivosModal = $canEdit && $errors->has('import_file');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Acreditados</h2>
                <p class="panel-text">Gestion humana — personal acreditado (vigencia y estados automáticos)</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-acreditados-page"
        x-data="acreditacionesAcreditados({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
        })"
        @acreditaciones-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            @if (session('import_failures'))
                <div class="alert alert--danger cursos-registros-page__alert">
                    <p class="mb-2">Errores de importación (máx. 50 en pantalla):</p>
                    <ul class="mb-2">
                        @foreach (session('import_failures') as $failure)
                            <li>
                                Fila {{ $failure['row'] ?? '?' }}
                                @if (! empty($failure['identifier']))
                                    ({{ $failure['identifier'] }})
                                @endif
                                : {{ $failure['reason'] ?? '' }}
                            </li>
                        @endforeach
                    </ul>
                    @if (session('import_report_token'))
                        <a
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            href="{{ route('gestion-humana.acreditaciones.acreditados.import-report', session('import_report_token')) }}"
                            title="Descargar reporte"
                            aria-label="Descargar reporte"
                        >
                            <x-lucide-download width="18" height="18" aria-hidden="true" />
                        </a>
                    @endif
                </div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <form method="GET" action="{{ route('gestion-humana.acreditaciones.acreditados') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_document_number">Cédula</label>
                                    <input id="filter_document_number" name="document_number" type="text" class="form-input" value="{{ $filters['document_number'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_cargo">Cargo</label>
                                    <input id="filter_cargo" name="cargo" type="text" class="form-input" value="{{ $filters['cargo'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_cargo_apo">CARGO APO</label>
                                    <x-searchable-select
                                        id="filter_cargo_apo"
                                        name="cargo_apo"
                                        :options="$filterCargoApoOptions"
                                        :value="$filters['cargo_apo']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado">Estado</label>
                                    <x-searchable-select
                                        id="filter_estado"
                                        name="estado"
                                        :options="$filterEstadoOptions"
                                        :value="$filters['estado']"
                                        placeholder="Todos"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia_desde">VIGEN.ACR desde</label>
                                    <input id="filter_vigencia_desde" name="vigencia_desde" type="date" class="form-input" value="{{ $filters['vigencia_desde'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia_hasta">VIGEN.ACR hasta</label>
                                    <input id="filter_vigencia_hasta" name="vigencia_hasta" type="date" class="form-input" value="{{ $filters['vigencia_hasta'] }}">
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Filtrar"
                                        aria-label="Filtrar"
                                    >
                                        <x-lucide-search width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <a
                                        href="{{ route('gestion-humana.acreditaciones.acreditados') }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar filtros"
                                        aria-label="Limpiar filtros"
                                    >
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <a
                                        href="{{ $exportUrl }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Exportar a Excel"
                                        aria-label="Exportar a Excel"
                                    >
                                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>
                            <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;">
                                El rango de fechas filtra por <strong>VIGEN.ACR</strong> (vencimiento).
                            </p>
                        </form>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">
                                <strong id="acreditados-count">…</strong>
                                <span id="acreditados-count-label">registro(s)</span>
                            </p>

                            @if ($canEdit)
                                <div class="cursos-registros-page__table-actions">
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Plantilla masivos — importar"
                                        aria-label="Plantilla masivos — importar"
                                        x-on:click.prevent="$dispatch('open-modal', 'acreditaciones-masivos')"
                                    >
                                        <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Nuevo acreditado"
                                        aria-label="Nuevo acreditado"
                                        x-on:click.prevent="$dispatch('open-modal', 'acreditaciones-nuevo')"
                                    >
                                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="acreditados-datatable"
                            class="data-table js-acreditados-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>CEDULA</th>
                                    <th>NOMBRE COMPLETO</th>
                                    <th>CARGO</th>
                                    <th>CARGO APO</th>
                                    <th>VIGEN.ACR</th>
                                    <th>ESTADO</th>
                                    <th>OBSERVACIONES</th>
                                    <th>FECHA SOLICITUD</th>
                                    @if ($canEdit)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                @include('areas.gestion_humana.acreditaciones.partials.nuevo-modal', [
                    'cargoApoOptions' => $cargoApoOptions,
                    'lookupUrl' => $lookupUrl,
                    'show' => $showNuevoModal,
                ])

                @include('areas.gestion_humana.acreditaciones.partials.masivos-modal', [
                    'canEdit' => $canEdit,
                    'exportUrl' => $exportUrl,
                    'importTemplateUrl' => $importTemplateUrl,
                    'importUrl' => $importUrl,
                    'show' => $showMasivosModal,
                ])

                <div
                    class="cursos-registros-page__modal"
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="editOpen = false"></div>
                    <div class="cursos-registros-page__modal-panel panel" role="dialog" aria-modal="true">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Editar acreditado</h3>
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Cerrar"
                                aria-label="Cerrar"
                                @click="editOpen = false"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="panel__body">
                            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form">
                                @csrf
                                @method('PATCH')
                                <div class="cursos-registros-page__form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="edit_document_number">CEDULA</label>
                                        <input
                                            id="edit_document_number"
                                            name="document_number"
                                            type="text"
                                            class="form-input"
                                            maxlength="50"
                                            required
                                            x-model="editForm.document_number"
                                            @blur="lookupName($event.target.value, 'edit')"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_full_name">NOMBRE COMPLETO</label>
                                        <input
                                            id="edit_full_name"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            readonly
                                            x-model="editForm.full_name"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_cargo">CARGO</label>
                                        <input
                                            id="edit_cargo"
                                            name="cargo"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            required
                                            x-model="editForm.cargo"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_cargo_apo">CARGO APO</label>
                                        <select id="edit_cargo_apo" name="cargo_apo" class="form-input" required x-model="editForm.cargo_apo">
                                            @foreach ($cargoApoOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_vigencia_acr">VIGEN.ACR</label>
                                        <input
                                            id="edit_vigencia_acr"
                                            name="vigencia_acr"
                                            type="date"
                                            class="form-input"
                                            x-model="editForm.vigencia_acr"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_fecha_solicitud">FECHA SOLICITUD</label>
                                        <input
                                            id="edit_fecha_solicitud"
                                            name="fecha_solicitud"
                                            type="date"
                                            class="form-input"
                                            x-model="editForm.fecha_solicitud"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_estado">ESTADO</label>
                                        <input
                                            id="edit_estado"
                                            type="text"
                                            class="form-input"
                                            readonly
                                            x-model="editForm.estado_label"
                                        >
                                    </div>
                                    <div class="form-field cursos-registros-page__form-span">
                                        <label class="form-label" for="edit_observaciones">OBSERVACIONES</label>
                                        <textarea
                                            id="edit_observaciones"
                                            name="observaciones"
                                            class="form-input"
                                            rows="2"
                                            x-model="editForm.observaciones"
                                        ></textarea>
                                    </div>
                                </div>
                                <p class="panel-text" style="font-size:0.85rem;">
                                    El estado se recalcula automáticamente al guardar. Indique al menos una fecha.
                                </p>
                                <div class="cursos-registros-page__form-actions">
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Actualizar"
                                        aria-label="Actualizar"
                                    >
                                        <x-lucide-save width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function acreditacionesAcreditados(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    editOpen: false,
                    editForm: {
                        document_number: '',
                        full_name: '',
                        cargo: '',
                        cargo_apo: '',
                        vigencia_acr: '',
                        fecha_solicitud: '',
                        estado: '',
                        estado_label: '',
                        observaciones: '',
                        update_url: '',
                    },
                    openEdit(detail) {
                        this.editForm = {
                            document_number: detail?.document_number || '',
                            full_name: detail?.full_name || '',
                            cargo: detail?.cargo || '',
                            cargo_apo: detail?.cargo_apo || '',
                            vigencia_acr: detail?.vigencia_acr || '',
                            fecha_solicitud: detail?.fecha_solicitud || '',
                            estado: detail?.estado || '',
                            estado_label: detail?.estado_label || detail?.estado || '',
                            observaciones: detail?.observaciones || '',
                            update_url: detail?.update_url || '',
                        };
                        this.editOpen = true;
                    },
                    async lookupName(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (!value || !this.lookupUrl) return;
                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (data.found && data.full_name) {
                                if (mode === 'edit') {
                                    this.editForm.document_number = data.document_number || value;
                                    this.editForm.full_name = data.full_name;
                                }
                            }
                        } catch (e) {}
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('[data-acreditaciones-import-form]');
                if (form) {
                    const fileInput = form.querySelector('[data-acreditaciones-import-file]');
                    const fileName = form.querySelector('[data-acreditaciones-import-name]');
                    const submitBtn = form.querySelector('[data-acreditaciones-import-submit]');
                    const loading = document.querySelector('[data-acreditaciones-import-loading]');

                    fileInput?.addEventListener('change', () => {
                        const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                        if (fileName) {
                            fileName.textContent = name;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = !fileInput.files?.length;
                        }
                    });

                    form.addEventListener('submit', () => {
                        if (loading) {
                            loading.hidden = false;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.setAttribute('aria-busy', 'true');
                            submitBtn.title = 'Importando…';
                            submitBtn.setAttribute('aria-label', 'Importando…');
                        }
                    });
                }

                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const $table = window.jQuery('.js-acreditados-datatable');
                if (!$table.length) {
                    return;
                }

                const wrap = $table.closest('.data-table-wrap');
                const reveal = function () {
                    wrap.removeClass('data-table-wrap--booting');
                };
                const updateMeta = function (count) {
                    const el = document.getElementById('acreditados-count');
                    if (el) {
                        el.textContent = String(count);
                    }
                };

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay acreditados para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[0, 'asc']],
                    columnDefs: canEdit
                        ? [{ targets: [8], orderable: false, searchable: false }]
                        : [],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    reveal();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateMeta(json.recordsFiltered);
                    }
                });

                $table.on('click', '.js-acreditado-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-acreditado-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('acreditaciones-open-edit', { detail: row }));
                    } catch (e) {}
                });
            });
        </script>
    @endpush
</x-app-layout>
