<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Mis indicadores: estado anual de los indicadores que el usuario captura. --}}
    <div class="page-section planeacion-page planeacion-mis-indicadores-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="planeacion-captura-count">…</strong>
                            <span>indicador(es) asignados</span>
                        </p>
                        <form method="GET" action="{{ route('calidad.planeacion.captura') }}" class="planeacion-mis-indicadores__year">
                            <div class="form-field">
                                <label class="form-label" for="mis_indicadores_year">Año</label>
                                <x-searchable-select
                                    id="mis_indicadores_year"
                                    name="year"
                                    :options="$yearOptions"
                                    :value="(string) $year"
                                    :allow-clear="false"
                                />
                            </div>
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Ver año" aria-label="Ver año">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                        </form>
                    </div>

                    <div class="data-table-wrap data-table-wrap--booting">
                        <table
                            class="data-table js-planeacion-captura-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                        >
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Versión</th>
                                    <th>Capturados</th>
                                    <th>Estado</th>
                                    <th>Último resultado</th>
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

            const $table = $('.js-planeacion-captura-datatable');
            if (! $table.length) {
                return;
            }

            const api = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: $table.data('dt-url') },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                    emptyTable: 'Aún no hay indicadores asignados para capturar en este año.',
                },
                dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                pageLength: 25,
                responsive: false,
                order: [[0, 'asc']],
                columnDefs: [{ targets: [6], orderable: false, searchable: false }],
            });

            api.on('xhr.dt', function (_event, _settings, json) {
                $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                const el = document.getElementById('planeacion-captura-count');
                if (el && json && typeof json.recordsFiltered !== 'undefined') {
                    el.textContent = json.recordsFiltered;
                }
            });
        });
    </script>
</x-app-layout>
