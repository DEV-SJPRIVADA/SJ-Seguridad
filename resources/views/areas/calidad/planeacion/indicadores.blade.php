<x-app-layout>
    @php
        $hasActiveFilters = ($filters['status'] ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Listado de indicadores: filtros compactos y DataTables server-side. --}}
    <div class="page-section planeacion-page req-manage-page">
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
                            <form method="GET" action="{{ route('calidad.planeacion.indicadores') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_status">Estado</label>
                                        <x-searchable-select
                                            id="filter_status"
                                            name="status"
                                            :options="$statusOptions"
                                            :value="$filters['status'] ?? ''"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('calidad.planeacion.indicadores') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="planeacion-indicadores-count">…</strong>
                            <span>indicador(es)</span>
                        </p>
                        <div class="cursos-registros-page__table-actions">
                            @if ($canEdit)
                                <a href="{{ route('calidad.planeacion.indicadores.create') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Nuevo indicador" aria-label="Nuevo indicador">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap data-table-wrap--booting">
                        <table
                            class="data-table js-planeacion-indicadores-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                        >
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Versión</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $ = window.jQuery;
            if (! $ || ! $.fn.DataTable) {
                return;
            }

            const $table = $('.js-planeacion-indicadores-datatable');
            if (! $table.length) {
                return;
            }

            const api = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: $table.data('dt-url') },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                    emptyTable: 'No hay indicadores para este filtro.',
                },
                dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                pageLength: 25,
                responsive: false,
                order: [[0, 'asc']],
                columnDefs: [{ targets: [4], orderable: false, searchable: false }],
            });

            api.on('xhr.dt', function (_event, _settings, json) {
                $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                const el = document.getElementById('planeacion-indicadores-count');
                if (el && json && typeof json.recordsFiltered !== 'undefined') {
                    el.textContent = json.recordsFiltered;
                }
            });
        });
    </script>

    @if ($canEdit)
        @include('areas.calidad.planeacion.partials.confirm-archive-modal')
    @endif
</x-app-layout>
