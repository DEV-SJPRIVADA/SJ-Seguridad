<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Empleados</h2>
                <p class="panel-text">Gestion humana — lista de espera y ficha de empleados contratados</p>
            </div>
        </div>
    </x-slot>

    @php
        $currentEstado = $filters['estado'] ?? 'en_ficha';
        $employmentStatusMode = $filters['employment_status_mode'] ?? 'default_activo';
        $employmentStatusQueryParam = match ($employmentStatusMode) {
            'todos' => 'todos',
            'desvinculado' => 'desvinculado',
            'activo' => 'activo',
            default => null,
        };
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($currentEstado !== 'en_ficha')
            || in_array($employmentStatusMode, ['todos', 'desvinculado'], true);

        $entriesQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'estado' => array_key_exists('estado', $overrides) ? $overrides['estado'] : ($filters['estado'] ?: null),
            'employment_status' => array_key_exists('employment_status', $overrides) ? $overrides['employment_status'] : $employmentStatusQueryParam,
            'fecha_desde' => array_key_exists('fecha_desde', $overrides) ? $overrides['fecha_desde'] : null,
            'fecha_hasta' => array_key_exists('fecha_hasta', $overrides) ? $overrides['fecha_hasta'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $pendingActive = $currentEstado === 'pendientes';
        $pendingHref = $pendingActive
            ? route('gestion-humana.ficha-empleados.employees.index', array_filter([
                'q' => $filters['q'] ?: null,
                'employment_status' => $employmentStatusQueryParam,
            ]))
            : route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['estado' => 'pendientes', 'employment_status' => null]));
    @endphp

    <div class="page-section ficha-empleados-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                @php
                    $importResult = session('import_result');
                    $importHasErrors = is_array($importResult) && (($importResult['failures_count'] ?? $importResult['failed'] ?? 0) > 0);
                @endphp
                <div class="alert {{ $importHasErrors ? 'alert--warning' : 'alert--success' }} ficha-empleados-page__alert">
                    {{ session('status') }}
                </div>
            @endif

            @include('partials.import-failure-report', [
                'importResult' => session('import_result'),
                'downloadRoute' => 'gestion-humana.ficha-empleados.employees.import-report',
            ])

            @if ($errors->has('export'))
                <div class="alert alert--danger ficha-empleados-page__alert">{{ $errors->first('export') }}</div>
            @endif

            @if ($errors->has('import_file'))
                <div class="alert alert--danger ficha-empleados-page__alert">{{ $errors->first('import_file') }}</div>
            @endif

            <div class="panel ficha-empleados-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-filters ficha-empleados-filters">
                        <div class="ficha-empleados-filters__bar">
                            <form method="GET" class="ficha-empleados-filters__search">
                                @if ($currentEstado !== 'en_ficha')
                                    <input type="hidden" name="estado" value="{{ $currentEstado }}">
                                @endif
                                @if ($employmentStatusQueryParam !== null)
                                    <input type="hidden" name="employment_status" value="{{ $employmentStatusQueryParam }}">
                                @endif
                                <label class="sr-only" for="ficha-search-input">Buscar</label>
                                <div class="ficha-empleados-filters__search-group">
                                    <input
                                        id="ficha-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre o codigo de requisicion"
                                    >
                                    <button
                                        type="submit"
                                        class="ficha-empleados-filters__icon-btn ficha-empleados-filters__icon-btn--ghost"
                                        title="Buscar"
                                        aria-label="Buscar"
                                    >
                                        <x-lucide-search width="30" height="30" aria-hidden="true" />
                                    </button>
                                    @if ($hasActiveFilters)
                                        <a
                                            href="{{ route('gestion-humana.ficha-empleados.employees.index') }}"
                                            class="ficha-empleados-filters__icon-btn ficha-empleados-filters__icon-btn--ghost"
                                            title="Limpiar filtros"
                                            aria-label="Limpiar filtros"
                                        >
                                            <x-lucide-filter-x width="20" height="20" aria-hidden="true" />
                                        </a>
                                    @endif
                                </div>
                            </form>

                            <div class="ficha-empleados-filters__status">
                                <a
                                    href="{{ $pendingHref }}"
                                    class="ficha-empleados-filters__pending-link {{ $pendingActive ? 'is-active' : '' }}"
                                    title="{{ $pendingActive ? 'Volver a empleados en ficha' : 'Ver pendientes' }}"
                                    aria-label="Pendientes: {{ number_format($pendingCount) }}"
                                >
                                    <x-ri-pass-pending-fill width="24" height="24" aria-hidden="true" />
                                    <span class="ficha-empleados-filters__pending-count">{{ number_format($pendingCount) }}</span>
                                </a>

                                @if ($currentEstado === 'en_ficha')
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll ficha-empleados-filters__pills">
                                        <a
                                            href="{{ route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['employment_status' => 'todos'])) }}"
                                            class="req-manage-filters__pill {{ $employmentStatusMode === 'todos' ? 'is-active' : '' }}"
                                        >Todos</a>
                                        @foreach ($employmentStatusLabels as $statusKey => $statusLabel)
                                            <a
                                                href="{{ route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['employment_status' => $statusKey])) }}"
                                                class="req-manage-filters__pill status-pill--ficha-{{ $statusKey }} {{ ($statusKey === 'activo' && in_array($employmentStatusMode, ['activo', 'default_activo'], true)) || $employmentStatusMode === $statusKey ? 'is-active' : '' }}"
                                            >{{ $statusLabel }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="ficha-empleados-filters__actions">
                                @if ($canManage)
                                    <a href="{{ route('gestion-humana.ficha-empleados.employees.create') }}" class="btn btn--primary btn--sm">Nuevo empleado</a>
                                @endif

                                @if ($currentEstado === 'en_ficha')
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon"
                                        title="Plantilla masivos — exportar e importar"
                                        aria-label="Plantilla masivos — exportar e importar"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'ficha-masivos')"
                                    >
                                        <x-lucide-upload width="20" height="20" aria-hidden="true" />
                                    </button>

                                    @if ($canExportArchive ?? false)
                                        <x-export-excel
                                            route="{{ route('gestion-humana.ficha-empleados.employees.export-archive-template', request()->query()) }}"
                                            label=""
                                            class="btn btn--secondary btn--sm"
                                        />
                                    @endif
                                @endif
                            </div>
                        </div>

                        <p class="req-manage-filters__meta ficha-empleados-filters__meta">
                            <strong id="ficha-entries-count">…</strong>
                            <span id="ficha-entries-count-label">registros</span>
                            @if ($currentEstado === 'en_ficha' && $employmentStatusMode !== 'todos')
                                · Estado: <strong>{{ $employmentStatusLabels[$employmentStatusMode === 'default_activo' ? 'activo' : $employmentStatusMode] ?? $employmentStatusMode }}</strong>
                            @endif
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table ficha-empleados-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="ficha-empleados-datatable"
                            class="data-table js-ficha-empleados-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-estado="{{ $currentEstado }}"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre completo</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha contrato</th>
                                    <th>Fecha ingreso</th>
                                    <th>Fecha retiro</th>
                                    <th>Estado</th>
                                    @if ($currentEstado === 'en_ficha')
                                        <th>Agregado por</th>
                                    @else
                                        <th>Acciones</th>
                                    @endif
                                    <th class="sr-only">Enlace ficha</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($currentEstado === 'en_ficha')
                @include('areas.gestion_humana.ficha-empleados.partials.masivos-modal', [
                    'filters' => $filters,
                    'canManage' => $canManage,
                    'show' => $errors->has('export') || $errors->has('import_file') || (is_array(session('import_result')) && ((session('import_result.failures_count') ?? 0) > 0 || session('import_result.report_token'))),
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var $table = $('#ficha-empleados-datatable');
                var $wrap = $table.closest('.data-table-wrap--booting');

                function revealFichaTableWrap() {
                    if (!$wrap.length) {
                        return;
                    }

                    $wrap.removeClass('data-table-wrap--booting');
                    $wrap.find('.data-table-wrap__loader').attr('aria-busy', 'false');
                }

                function bindClickableFichaRows(tbody) {
                    tbody.addEventListener('click', function (event) {
                        if (event.target.closest('button, a, form, input, label, select, textarea')) {
                            return;
                        }

                        var row = event.target.closest('tr[data-ficha-href]');
                        if (!row) {
                            return;
                        }

                        window.location.assign(row.dataset.fichaHref);
                    });

                    tbody.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }

                        var row = event.target.closest('tr[data-ficha-href]');
                        if (!row) {
                            return;
                        }

                        event.preventDefault();
                        window.location.assign(row.dataset.fichaHref);
                    });
                }

                function updateFichaEntriesMeta(recordsFiltered) {
                    var countEl = document.getElementById('ficha-entries-count');
                    var labelEl = document.getElementById('ficha-entries-count-label');

                    if (!countEl || !labelEl) {
                        return;
                    }

                    var count = Number(recordsFiltered) || 0;
                    countEl.textContent = count.toLocaleString('es-CO');
                    labelEl.textContent = count === 1 ? 'registro' : 'registros';
                }

                if ($table.length && typeof $.fn.DataTable !== 'undefined') {
                    if ($.fn.DataTable.isDataTable($table[0])) {
                        $table.DataTable().destroy();
                    }

                    $table.closest('.req-manage-shell__table, .data-table-wrap').addClass('data-table-wrap--dt-compact');

                    var hrefColumnIndex = $table.find('thead th').length - 1;

                    var api = $table.DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: $table.data('dt-url'),
                            data: function (data) {
                                data.q = @json($filters['q'] ?? '');
                                data.estado = @json($filters['estado'] ?? 'en_ficha');
                                @if ($employmentStatusQueryParam !== null)
                                data.employment_status = @json($employmentStatusQueryParam);
                                @endif
                            },
                        },
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                            emptyTable: 'No hay registros para este filtro.',
                        },
                        dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                        pageLength: 10,
                        responsive: false,
                        order: [],
                        columnDefs: [
                            { targets: hrefColumnIndex, visible: false, searchable: false, orderable: false },
                            { targets: -2, orderable: false },
                        ],
                        createdRow: function (row, data) {
                            var href = data[hrefColumnIndex];

                            if (!href) {
                                return;
                            }

                            row.classList.add('ficha-empleados-row--clickable');
                            row.dataset.fichaHref = href;
                            row.tabIndex = 0;
                            row.setAttribute('role', 'link');
                        },
                    });

                    api.on('xhr.dt', function (_event, _settings, json) {
                        revealFichaTableWrap();

                        if (json && typeof json.recordsFiltered !== 'undefined') {
                            updateFichaEntriesMeta(json.recordsFiltered);
                        }
                    });

                    api.on('draw.dt', function () {
                        var tbody = $table.find('tbody')[0];

                        if (tbody && !tbody.dataset.fichaRowsBound) {
                            bindClickableFichaRows(tbody);
                            tbody.dataset.fichaRowsBound = '1';
                        }
                    });

                    (function setupFichaTableScroll() {
                        var $shell = $table.closest('.req-manage-shell');
                        var $wrapper = $table.closest('.dataTables_wrapper');
                        var $scroll = $wrapper.find('.req-manage-table-scroll').first();
                        var $bottom = $wrapper.find('.req-manage-dt-bottom').first();

                        if (!$scroll.length) {
                            return;
                        }

                        var updateScrollArea = function () {
                            var bottomHeight = $bottom.outerHeight(true) || 0;
                            var rect = $scroll[0].getBoundingClientRect();
                            var maxHeight = window.innerHeight - rect.top - bottomHeight - 16;

                            $scroll.css('max-height', Math.max(220, maxHeight) + 'px');
                        };

                        var debounce = function (fn, wait) {
                            var timer = null;

                            return function () {
                                if (timer) {
                                    clearTimeout(timer);
                                }

                                timer = setTimeout(fn, wait);
                            };
                        };

                        var scheduleUpdate = function () {
                            updateScrollArea();
                            setTimeout(updateScrollArea, 0);
                            setTimeout(updateScrollArea, 150);

                            $scroll.find('thead th').css({
                                position: 'sticky',
                                top: '0',
                                zIndex: '12',
                                backgroundColor: '#003366',
                            });

                            $scroll.find('table').css({
                                borderCollapse: 'separate',
                                borderSpacing: '0',
                            });
                        };

                        scheduleUpdate();
                        $(window).on('resize orientationchange', debounce(updateScrollArea, 100));
                        api.on('draw.dt-table-scroll', updateScrollArea);

                        if (typeof ResizeObserver !== 'undefined') {
                            new ResizeObserver(debounce(updateScrollArea, 100)).observe($shell[0]);
                        }
                    })();
                }

                document.querySelectorAll('[data-ficha-import-file]').forEach(function (input) {
                    var form = input.closest('[data-ficha-import-form]');
                    var nameEl = form?.querySelector('[data-ficha-import-name]');
                    var submitBtn = form?.querySelector('[data-ficha-import-submit]');
                    var chooseBtn = form?.querySelector('[data-ficha-import-choose]');
                    var loadingEl = document.querySelector('[data-ficha-import-loading]');

                    if (!nameEl || !submitBtn || !form) {
                        return;
                    }

                    input.addEventListener('change', function () {
                        var file = input.files && input.files[0];
                        nameEl.textContent = file ? file.name : 'Sin archivo seleccionado';
                        submitBtn.disabled = !file;
                    });

                    form.addEventListener('submit', function () {
                        if (loadingEl) {
                            loadingEl.hidden = false;
                        }

                        submitBtn.disabled = true;

                        if (chooseBtn) {
                            chooseBtn.classList.add('is-disabled');
                            chooseBtn.style.pointerEvents = 'none';
                        }

                        submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
