<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $hasFilters = $filters['q'] !== ''
            || $filters['estado'] !== \App\Models\PurchaseProvider::ESTADO_ACTIVO
            || ! $filters['solo_proveedores'];
    @endphp

    <div class="page-section req-manage-page purchase-providers-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="alert">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">Proveedores</h2>
                        <p class="page-subtitle">Catálogo de proveedores para órdenes de compra y evaluación post-entrega.</p>
                    </div>
                    @if ($canEdit)
                        <div class="pur-req-detail__toolbar-actions">
                            <a href="{{ $createUrl }}" class="btn btn--primary btn--sm">
                                <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                Nuevo proveedor
                            </a>
                        </div>
                    @endif
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
                                    <label class="form-label" for="filter_q">Buscar</label>
                                    <input
                                        id="filter_q"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Identificación, nombre, correo…"
                                    >
                                </div>
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
                                <div class="form-field">
                                    <span class="form-label">Catálogo</span>
                                    <label class="cursos-registros-page__checkbox-label" for="filter_solo_proveedores">
                                        <input type="hidden" name="solo_proveedores" value="0">
                                        <input
                                            id="filter_solo_proveedores"
                                            type="checkbox"
                                            name="solo_proveedores"
                                            value="1"
                                            @checked($filters['solo_proveedores'])
                                        >
                                        Solo proveedores
                                    </label>
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
                                    route="{{ $exportUrl }}"
                                    label=""
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Exportar Excel"
                                    aria-label="Exportar el catálogo a Excel"
                                />
                            </div>
                        </form>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="providers-count">…</strong>
                            <span id="providers-count-label">proveedor(es)</span>
                        </p>
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

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                    },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay proveedores para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"l><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    searching: false,
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

                api.on('error.dt', revealTable);
            });
        </script>
    @endpush
</x-app-layout>
