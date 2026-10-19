<x-app-layout>
    @php
        $hasFilters = $filters['q'] !== ''
            || $filters['estado'] !== \App\Models\PurchaseProvider::ESTADO_ACTIVO;
        $showImportModal = ($showImportModal ?? false) || $errors->has('import_file');
        // El resultado de la carga masiva se muestra en modal (no como alerta superior).
        $hasImportFlash = session()->has('import_done')
            || session()->has('import_result')
            || session()->has('import_failures')
            || session()->has('import_report_token');
    @endphp

    <div class="page-section req-manage-page purchase-providers-page">
        <div class="app-container">
            @if (session('status') && ! $hasImportFlash)
                <div class="alert alert--success" role="alert">{{ session('status') }}</div>
            @endif

            <x-import-result-modal
                download-route="purchase-requests.providers.import-report"
                :download-params="['module' => $module]"
            />

            <div class="page-header-inner purchase-requests-page__intro">
                <div>
                    <h2 class="page-title">Proveedores</h2>
                    <p class="page-subtitle">Catálogo de proveedores para órdenes de compra y evaluación post-entrega.</p>
                </div>
            </div>

            <div class="panel req-manage-shell">
                <div class="panel__body panel__body--compact">
                    <details class="req-manage-filters" @if ($hasFilters) open @endif>
                        <summary class="req-manage-filters__summary">
                            <span>Filtros del catálogo</span>
                        </summary>
                        <form method="get" action="{{ $indexUrl }}" class="req-manage-filters__form">
                            <div class="req-manage-filters__row">
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado">Estado</label>
                                    <x-searchable-select
                                        id="filter_estado"
                                        name="estado"
                                        :options="$estados"
                                        :value="$filters['estado']"
                                        search-placeholder="Buscar estado…"
                                        :allow-clear="false"
                                    />
                                </div>
                            </div>
                            <div class="req-manage-filters__actions" role="group" aria-label="Acciones de filtro">
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Aplicar filtros"
                                    aria-label="Aplicar filtros"
                                >
                                    <x-lucide-search width="18" height="18" aria-hidden="true" />
                                </button>
                                <a
                                    href="{{ $indexUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Limpiar filtros"
                                    aria-label="Limpiar filtros"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <x-export-excel
                                    id="providers-export-link"
                                    route="{{ $exportUrl }}"
                                    label=""
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Exportar Excel"
                                    aria-label="Exportar el catálogo a Excel"
                                />
                                <a
                                    href="{{ $importTemplateUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Descargar plantilla VINE01"
                                    aria-label="Descargar plantilla VINE01"
                                >
                                    <x-lucide-download width="18" height="18" aria-hidden="true" />
                                </a>
                                @if ($canEdit)
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Importar VINE01"
                                        aria-label="Importar proveedores VINE01"
                                        x-data
                                        x-on:click.prevent="$dispatch('open-modal', 'provider-import')"
                                    >
                                        <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                    </button>
                                @endif
                            </div>
                        </form>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="providers-count">…</strong>
                            <span id="providers-count-label">proveedor(es)</span>
                        </p>

                        @if ($canEdit)
                            <div class="cursos-registros-page__table-actions">
                                <a
                                    href="{{ $createUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nuevo proveedor"
                                    aria-label="Nuevo proveedor"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="data-table-wrap req-manage-shell__table data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="providers-datatable"
                            class="data-table"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Identificación</th>
                                    <th>Nombre / razón social</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Contacto</th>
                                    <th>Ciudad</th>
                                    <th>Días pago</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('modules.purchase-requests.providers.partials.import-modal', [
        'show' => $showImportModal,
        'importTemplateUrl' => $importTemplateUrl,
        'importUrl' => $importUrl,
    ])

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const $ = window.jQuery;
                const table = document.getElementById('providers-datatable');

                if (! $ || ! table || typeof $.fn.DataTable === 'undefined') {
                    return;
                }

                const $table = $(table);

                function revealTable() {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                }

                function updateCount(recordsFiltered) {
                    const countEl = document.getElementById('providers-count');
                    const labelEl = document.getElementById('providers-count-label');

                    if (! countEl || ! labelEl) {
                        return;
                    }

                    const count = Number(recordsFiltered) || 0;
                    countEl.textContent = count.toLocaleString('es-CO');
                    labelEl.textContent = count === 1 ? 'proveedor' : 'proveedores';
                }

                // El buscador es el de DataTables; el export debe llevar el término.
                function syncExport(searchValue) {
                    const link = document.getElementById('providers-export-link');

                    if (! link) {
                        return;
                    }

                    const url = new URL(link.getAttribute('href'), window.location.origin);

                    if (searchValue) {
                        url.searchParams.set('q', searchValue);
                    } else {
                        url.searchParams.delete('q');
                    }

                    link.setAttribute('href', url.toString());
                }

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                    },
                    search: {
                        search: @json($filters['q'] ?? ''),
                    },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay proveedores para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { targets: [8], orderable: false, searchable: false },
                    ],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    revealTable();

                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateCount(json.recordsFiltered);
                    }
                });

                api.on('search.dt', function () {
                    syncExport(api.search());
                });

                api.on('error.dt', revealTable);
            });
        </script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-provider-import-form]');

                if (! form) {
                    return;
                }

                const fileInput = form.querySelector('[data-provider-import-file]');
                const fileName = form.querySelector('[data-provider-import-name]');
                const submitBtn = form.querySelector('[data-provider-import-submit]');
                const loading = document.querySelector('[data-provider-import-loading]');

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
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    if (loading) {
                        loading.hidden = false;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
