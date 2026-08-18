<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.archivo.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Historias Laborales</h2>
                <p class="panel-text">Gestion humana — ubicacion documental de empleados (estantes y cajas)</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section archivo-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert {{ ($importHasFailures ?? false) ? 'alert--warning' : 'alert--success' }} archivo-page__alert">
                    {{ session('status') }}
                </div>
            @endif

            @include('partials.import-failure-report', [
                'importResult' => $importResult ?? session('import_result'),
                'downloadRoute' => 'gestion-humana.archivo.import-report',
            ])

            @if ($errors->has('import_file'))
                <div class="alert alert--danger archivo-page__alert">{{ $errors->first('import_file') }}</div>
            @endif

            @if ($errors->any() && ! $errors->has('import_file'))
                <div class="alert alert--danger archivo-page__alert">
                    @foreach ($errors->all() as $error)
                        <p class="archivo-page__inline-error">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Empleados en ficha</h3>
                                <p class="panel-text">Cedula, nombre o codigo de requisicion</p>
                            </div>
                            <div class="req-manage-filters__actions">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn--sm"
                                    title="Consulta multiple por cedulas"
                                    aria-label="Consulta multiple por cedulas"
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'archivo-consult')"
                                >
                                    <x-lucide-search width="16" height="16" aria-hidden="true" />
                                    Consulta multiple
                                </button>
                                @if ($canExportArchive ?? false)
                                    <x-export-excel
                                        route="{{ route('gestion-humana.ficha-empleados.employees.export-archive-template', request()->query()) }}"
                                        label="Exportar archivo"
                                        class="btn btn--secondary btn--sm"
                                    />
                                @endif
                                @if ($canManage)
                                    <button
                                        type="button"
                                        class="btn btn--secondary btn--sm"
                                        title="Importar estantes y cajas"
                                        aria-label="Importar estantes y cajas"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'archivo-import')"
                                    >
                                        <x-lucide-upload width="16" height="16" aria-hidden="true" />
                                        Importar
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar">
                            <form method="GET" class="req-manage-filters__search-col archivo-page__search-col">
                                @if ($filters['consultation'] ?? null)
                                    <input type="hidden" name="consultation" value="{{ $filters['consultation'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="archivo-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group">
                                    <input
                                        id="archivo-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre o codigo de requisicion"
                                    >
                                    <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                                </div>
                            </form>
                        </div>

                        <p class="req-manage-filters__meta">
                            <strong id="archivo-entries-count">…</strong>
                            <span id="archivo-entries-count-label">empleados</span>
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                            @if ($activeConsultation ?? null)
                                · Consulta #{{ $activeConsultation->id }}
                            @endif
                        </p>
                    </div>

                    @if ($activeConsultation ?? null)
                        <div class="archivo-consult-banner">
                            <div class="archivo-consult-banner__body">
                                <p class="archivo-consult-banner__title">Consulta activa #{{ $activeConsultation->id }}</p>
                                <p class="archivo-consult-banner__meta">
                                    {{ $activeConsultation->created_at?->format('d/m/Y H:i') }}
                                    · {{ $activeConsultation->user?->name ?: 'Usuario' }}
                                    · {{ $activeConsultation->documents_matched }}/{{ $activeConsultation->documents_requested }} cedula(s) en ficha
                                </p>
                                <p class="archivo-consult-banner__types">
                                    <strong>Motivos:</strong> {{ implode(' · ', $activeConsultation->typeLabels()) }}
                                </p>
                                @if ($activeConsultation->delivered_to)
                                    <p class="archivo-consult-banner__docs">
                                        <strong>Entregada a:</strong> {{ $activeConsultation->delivered_to }}
                                    </p>
                                @endif
                                <p class="archivo-consult-banner__docs">
                                    <strong>Cedulas:</strong> {{ implode(', ', $activeConsultation->document_numbers ?? []) }}
                                </p>
                                @if (! empty($activeConsultation->documents_not_found))
                                    <p class="archivo-consult-banner__missing">
                                        <strong>No encontradas:</strong> {{ implode(', ', $activeConsultation->documents_not_found) }}
                                    </p>
                                @endif
                            </div>
                            <div class="archivo-consult-banner__actions">
                                <a href="{{ route('gestion-humana.archivo.consultation-history.index') }}" class="btn btn--secondary btn--sm">
                                    Ver historial
                                </a>
                                <a href="{{ route('gestion-humana.archivo.labor-histories.index', array_filter(['q' => $filters['q'] ?? null])) }}" class="btn btn--secondary btn--sm">
                                    Quitar filtro
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="data-table-wrap req-manage-shell__table archivo-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="archivo-labor-histories-datatable"
                            class="data-table js-archivo-labor-histories-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha ingreso</th>
                                    <th>Estado vinculo</th>
                                    <th>Recontratable</th>
                                    <th>Fecha retiro</th>
                                    <th>Estante</th>
                                    <th>Caja</th>
                                    @if ($canManage)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @include('areas.gestion_humana.archivo.partials.consult-modal', [
                'consultationTypes' => $consultationTypes ?? config('employee_ficha.archive_consultation_types', []),
                'show' => $errors->has('documents') || $errors->has('consultation_types') || $errors->has('consultation_types.*'),
            ])

            @if ($canManage)
                @include('areas.gestion_humana.archivo.partials.import-modal', [
                    'canManage' => $canManage,
                    'canExportArchive' => $canExportArchive ?? false,
                    'show' => $errors->has('import_file') || (is_array(session('import_result')) && ((session('import_result.failures_count') ?? 0) > 0 || session('import_result.report_token'))),
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var $table = $('#archivo-labor-histories-datatable');
                var $wrap = $table.closest('.data-table-wrap--booting');

                function revealArchivoTableWrap() {
                    if (!$wrap.length) {
                        return;
                    }

                    $wrap.removeClass('data-table-wrap--booting');
                    $wrap.find('.data-table-wrap__loader').attr('aria-busy', 'false');
                }

                function updateArchivoEntriesMeta(recordsFiltered) {
                    var countEl = document.getElementById('archivo-entries-count');
                    var labelEl = document.getElementById('archivo-entries-count-label');

                    if (!countEl || !labelEl) {
                        return;
                    }

                    var count = Number(recordsFiltered) || 0;
                    countEl.textContent = count.toLocaleString('es-CO');
                    labelEl.textContent = count === 1 ? 'empleado' : 'empleados';
                }

                if ($table.length && typeof $.fn.DataTable !== 'undefined') {
                    if ($.fn.DataTable.isDataTable($table[0])) {
                        $table.DataTable().destroy();
                    }

                    $table.closest('.req-manage-shell__table, .data-table-wrap').addClass('data-table-wrap--dt-compact');

                    var columnDefs = [];
                    @if ($canManage)
                    columnDefs.push({ targets: -1, orderable: false, searchable: false });
                    columnDefs.push({ targets: [-3, -2], orderable: false });
                    @endif

                    var api = $table.DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: $table.data('dt-url'),
                            data: function (data) {
                                data.q = @json($filters['q'] ?? '');
                                @if ($filters['consultation'] ?? null)
                                data.consultation = @json($filters['consultation']);
                                @endif
                            },
                        },
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                            emptyTable: 'No hay empleados en ficha.',
                        },
                        dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                        pageLength: 10,
                        responsive: false,
                        order: [],
                        columnDefs: columnDefs,
                    });

                    api.on('xhr.dt', function (_event, _settings, json) {
                        revealArchivoTableWrap();

                        if (json && typeof json.recordsFiltered !== 'undefined') {
                            updateArchivoEntriesMeta(json.recordsFiltered);
                        }
                    });

                    (function setupArchivoTableScroll() {
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

                        if (typeof ResizeObserver !== 'undefined' && $shell.length) {
                            new ResizeObserver(debounce(updateScrollArea, 100)).observe($shell[0]);
                        }
                    })();
                }

                var fileInput = document.querySelector('[data-archivo-import-file]');
                var fileName = document.querySelector('[data-archivo-import-name]');
                var submitBtn = document.querySelector('[data-archivo-import-submit]');
                var form = document.querySelector('[data-archivo-import-form]');
                var loading = document.querySelector('[data-archivo-import-loading]');

                if (fileInput && fileName && submitBtn) {
                    fileInput.addEventListener('change', function () {
                        var name = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                        fileName.textContent = name ? name.name : 'Sin archivo seleccionado';
                        submitBtn.disabled = !name;
                    });
                }

                if (form && loading && submitBtn) {
                    form.addEventListener('submit', function () {
                        loading.hidden = false;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
