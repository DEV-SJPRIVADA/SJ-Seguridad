<x-app-layout>
    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || (($filters['cola'] ?? 'pendientes') !== 'pendientes');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.mt_st_04.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Validaciones: activos en Ficha que requieren psicofísicos y no están en matriz --}}
    <div class="page-section mt-st-04-page mt-st-04-validaciones-page req-manage-page cursos-registros-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <p class="panel-text" style="margin:0 0 0.75rem;font-size:0.9rem;">
                        Personal <strong>activo en Ficha</strong> que <strong>requiere psicofísicos</strong> y aún
                        <strong>no tiene registro en la Matriz</strong>. Use el icono + para agregar o el de omitir
                        si no aplica el examen (también editable en Ficha empleados).
                    </p>

                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('gestion-humana.mt-st-04.validaciones') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_q">Buscar (cédula / nombre)</label>
                                        <input id="filter_q" name="q" type="text" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula o nombre">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_cola">Cola</label>
                                        <x-searchable-select
                                            id="filter_cola"
                                            name="cola"
                                            :options="$filterColaOptions"
                                            :value="$filters['cola']"
                                            placeholder="Pendientes"
                                            :allow-clear="false"
                                        />
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
                                            href="{{ route('gestion-humana.mt-st-04.validaciones') }}"
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

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="mt-st-04-validaciones-count">…</strong>
                            <span>persona(s)</span>
                        </p>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="mt-st-04-validaciones-datatable"
                            class="data-table js-mt-st-04-validaciones-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                            aria-label="Validaciones MT-ST-04"
                        >
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Ciudad</th>
                                    <th>Puesto</th>
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
                @include('areas.gestion_humana.mt_st_04.partials.nuevo-modal', [
                    'lookupUrl' => $lookupUrl,
                    'siNoOptions' => $siNoOptions,
                    'show' => $showNuevoModal,
                    'returnTo' => 'validaciones',
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const $table = window.jQuery('.js-mt-st-04-validaciones-datatable');
                if (!$table.length) {
                    return;
                }

                const wrap = $table.closest('.data-table-wrap');
                const reveal = function () {
                    wrap.removeClass('data-table-wrap--booting');
                };
                const updateMeta = function (count) {
                    const el = document.getElementById('mt-st-04-validaciones-count');
                    if (el) {
                        el.textContent = String(count);
                    }
                };

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                const columnDefs = canEdit
                    ? [{ targets: [5], orderable: false, searchable: false }]
                    : [];

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay personas en esta cola de validación.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[1, 'asc']],
                    columnDefs: columnDefs,
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    reveal();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateMeta(json.recordsFiltered);
                    }
                });

                $table.on('click', '.js-mt-st-04-validacion-add', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-mt-st-04-add') || '{}');
                        window.dispatchEvent(new CustomEvent('mt-st-04-prefill-nuevo', { detail: row }));
                    } catch (e) {}
                });
            });
        </script>
    @endpush
</x-app-layout>
