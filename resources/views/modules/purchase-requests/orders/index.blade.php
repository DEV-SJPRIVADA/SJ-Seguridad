<x-app-layout>
    @php
        $hasFilters = $filters['estado'] !== \App\Services\PurchaseRequests\PurchaseOrderDatatableService::ESTADO_TODOS
            || $filters['tipo'] !== \App\Services\PurchaseRequests\PurchaseOrderDatatableService::TIPO_TODOS
            || $filters['proveedor_id'] !== null
            || $filters['desde'] !== null
            || $filters['hasta'] !== null;
    @endphp

    <div class="page-section req-manage-page purchase-orders-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="alert">{{ session('status') }}</div>
            @endif

            <div class="panel purchase-orders-page__panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    {{-- Filtros del tablero (colapsables). El buscador global vive en la tabla. --}}
                    <details
                        class="req-manage-filters req-manage-filters__panel purchase-orders-page__filters"
                        @if ($hasFilters) open @endif
                    >
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="get" action="{{ $indexUrl }}" class="req-manage-filters">
                                <div class="purchase-orders-page__filter-grid">
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
                                        <label class="form-label" for="filter_tipo">Origen</label>
                                        <x-searchable-select
                                            id="filter_tipo"
                                            name="tipo"
                                            :options="$tipos"
                                            :value="$filters['tipo']"
                                            search-placeholder="Buscar origen…"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_proveedor">Proveedor</label>
                                        <x-searchable-select
                                            id="filter_proveedor"
                                            name="proveedor_id"
                                            :options="$proveedores"
                                            :value="$filters['proveedor_id']"
                                            placeholder="Todos los proveedores"
                                            search-placeholder="Buscar proveedor…"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_desde">Desde</label>
                                        <input
                                            id="filter_desde"
                                            type="date"
                                            name="desde"
                                            class="form-input"
                                            value="{{ $filters['desde'] }}"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_hasta">Hasta</label>
                                        <input
                                            id="filter_hasta"
                                            type="date"
                                            name="hasta"
                                            class="form-input"
                                            value="{{ $filters['hasta'] }}"
                                        >
                                    </div>
                                    <div class="form-field purchase-orders-page__filter-actions">
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
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    {{-- Barra de acciones siempre visible: conteo de órdenes emitidas. --}}
                    <div class="cursos-registros-page__table-toolbar purchase-orders-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="orders-count">…</strong>
                            <span id="orders-count-label">orden(es) de compra</span>
                        </p>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table purchase-orders-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="orders-datatable"
                            class="data-table"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>OC</th>
                                    <th>FECHA</th>
                                    <th>PROVEEDOR</th>
                                    <th>IDENTIFICACIÓN</th>
                                    <th>ORIGEN</th>
                                    <th>CIUDAD ENTREGA</th>
                                    <th>LÍNEAS</th>
                                    <th>TOTAL</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
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
                const table = document.getElementById('orders-datatable');

                if (! $ || ! table || typeof $.fn.DataTable === 'undefined') {
                    return;
                }

                const $table = $(table);

                function revealTable() {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                }

                function updateCount(recordsFiltered) {
                    const countEl = document.getElementById('orders-count');
                    const labelEl = document.getElementById('orders-count-label');

                    if (! countEl) {
                        return;
                    }

                    const count = Number(recordsFiltered) || 0;
                    countEl.textContent = count.toLocaleString('es-CO');

                    if (labelEl) {
                        labelEl.textContent = count === 1 ? 'orden de compra' : 'órdenes de compra';
                    }
                }

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                    },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay órdenes de compra para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[1, 'desc']],
                    columnDefs: [
                        { targets: [9], orderable: false, searchable: false },
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
