<x-app-layout>
    @php
        $hasActiveFilters = ($filters['anio'] ?? '') !== ''
            || ($filters['mes'] ?? '') !== ''
            || ($filters['categoria'] ?? '') !== ''
            || ($filters['nombre_curso'] ?? '') !== ''
            || ($filters['numero_id'] ?? '') !== ''
            || ($filters['nombre'] ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.formacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section formacion-formaciones-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('gestion-humana.formacion.formaciones') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_anio">Año</label>
                                        <x-searchable-select
                                            id="filter_anio"
                                            name="anio"
                                            :options="$filterAnioOptions"
                                            :value="$filters['anio']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_mes">Mes</label>
                                        <x-searchable-select
                                            id="filter_mes"
                                            name="mes"
                                            :options="$filterMesOptions"
                                            :value="$filters['mes']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_categoria">Categoría</label>
                                        <x-searchable-select
                                            id="filter_categoria"
                                            name="categoria"
                                            :options="$filterCategoriaOptions"
                                            :value="$filters['categoria']"
                                            placeholder="Todas"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_nombre_curso">Curso</label>
                                        <x-searchable-select
                                            id="filter_nombre_curso"
                                            name="nombre_curso"
                                            :options="$filterCursoOptions"
                                            :value="$filters['nombre_curso']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_numero_id">Número ID</label>
                                        <input
                                            id="filter_numero_id"
                                            name="numero_id"
                                            type="text"
                                            class="form-input"
                                            value="{{ $filters['numero_id'] }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_nombre">Nombre</label>
                                        <input
                                            id="filter_nombre"
                                            name="nombre"
                                            type="text"
                                            class="form-input"
                                            value="{{ $filters['nombre'] }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.formacion.formaciones') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                        <x-export-excel
                                            route="{{ $exportUrl }}"
                                            label=""
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Exportar a Excel (respeta filtros)"
                                            aria-label="Exportar a Excel"
                                        />
                                        @if ($canEdit)
                                            <button
                                                type="button"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                title="Plantilla e importar (reemplaza todos)"
                                                aria-label="Plantilla e importar"
                                                x-data=""
                                                x-on:click.prevent="$dispatch('open-modal', 'formacion-import')"
                                            >
                                                <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="formacion-formaciones-count">…</strong>
                            <span>registro(s)</span>
                        </p>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="formacion-formaciones-datatable"
                            class="data-table js-formacion-formaciones-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Número ID</th>
                                    <th>Nombre completo</th>
                                    <th>Fecha inicio</th>
                                    <th>Mes</th>
                                    <th>Año</th>
                                    <th>Curso</th>
                                    <th>Calificación</th>
                                    <th>Categoría</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canEdit)
        @include('areas.gestion_humana.formacion.partials.import-modal', [
            'importTemplateUrl' => $importTemplateUrl,
            'importUrl' => $importUrl,
            'show' => $showImportModal ?? false,
        ])
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    return;
                }

                const $table = $('.js-formacion-formaciones-datatable');
                if (! $table.length) {
                    return;
                }

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay registros para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 25,
                    responsive: false,
                    order: [[2, 'desc']],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('formacion-formaciones-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                @if ($canEdit)
                const form = document.querySelector('[data-formacion-import-form]');
                if (form) {
                    const fileInput = form.querySelector('[data-formacion-import-file]');
                    const fileName = form.querySelector('[data-formacion-import-name]');
                    const submitBtn = form.querySelector('[data-formacion-import-submit]');
                    const confirmBox = form.querySelector('[data-formacion-import-confirm]');
                    const loading = document.querySelector('[data-formacion-import-loading]');

                    const syncSubmit = () => {
                        const hasFile = !!(fileInput?.files && fileInput.files.length > 0);
                        const confirmed = !!(confirmBox && confirmBox.checked);
                        if (submitBtn) {
                            submitBtn.disabled = !(hasFile && confirmed);
                        }
                    };

                    fileInput?.addEventListener('change', () => {
                        const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                        if (fileName) {
                            fileName.textContent = name;
                        }
                        syncSubmit();
                    });

                    confirmBox?.addEventListener('change', syncSubmit);
                    syncSubmit();

                    form.addEventListener('submit', () => {
                        if (loading) {
                            loading.hidden = false;
                            loading.setAttribute('aria-busy', 'true');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                        }
                    });
                }
                @endif
            });
        </script>
    @endpush
</x-app-layout>
