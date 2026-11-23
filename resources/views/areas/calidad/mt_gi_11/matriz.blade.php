{{--
    Listado MT-GI-11: filtros, pills de estado, toolbar masiva y DataTables server-side.

    Todo se opera por `id`; el consecutivo puede repetirse entre versiones.
--}}
@php
    $estadoActivo = (string) ($filters['estado_id'] ?? '');
    $queryBase = collect($filters)
        ->except(['estado_id', 'q'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
    $hasActiveFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.mt_gi_11.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section mt-gi-11-page mt-gi-11-matriz-page req-manage-page cursos-registros-page"
        x-data="{
            deleteOpen: false,
            deleteUrl: '',
            deleteLabel: '',
            openDelete(url, label) {
                this.deleteUrl = url;
                this.deleteLabel = label;
                this.deleteOpen = true;
            },
        }"
        @mt-gi-11-delete.window="openDelete($event.detail.url, $event.detail.label)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            <x-import-result-modal download-route="calidad.mt-gi-11.matriz.plantilla" />

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    {{-- Filtros: fecha, proceso, consecutivo, tipo de documento --}}
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('calidad.mt-gi-11.matriz') }}" class="req-manage-filters">
                                <input type="hidden" name="estado_id" value="{{ $estadoActivo }}">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_desde">Fecha solicitud desde</label>
                                        <input id="filter_fecha_desde" name="fecha_desde" type="date" class="form-input" value="{{ $filters['fecha_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_hasta">Fecha solicitud hasta</label>
                                        <input id="filter_fecha_hasta" name="fecha_hasta" type="date" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_proceso_id">Proceso</label>
                                        <x-searchable-select id="filter_proceso_id" name="proceso_id" :options="$options['procesos']" :value="$filters['proceso_id']" placeholder="Todos" search-placeholder="Buscar proceso…" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_consecutivo">Consecutivo</label>
                                        <input id="filter_consecutivo" name="consecutivo" type="text" class="form-input" maxlength="60" value="{{ $filters['consecutivo'] }}" placeholder="Coincidencia parcial">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_tipo_documento_id">Tipo de documento</label>
                                        <x-searchable-select id="filter_tipo_documento_id" name="tipo_documento_id" :options="$options['tipos_documento']" :value="$filters['tipo_documento_id']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('calidad.mt-gi-11.matriz') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>
                            </form>

                            {{-- Filtro por botón de estado: conserva los demás filtros --}}
                            <div class="mt-gi-11-page__estado-filters" role="group" aria-label="Filtrar por estado">
                                <a href="{{ route('calidad.mt-gi-11.matriz', $queryBase) }}" class="module-tab {{ $estadoActivo === '' ? 'module-tab--active' : '' }}">Todos</a>
                                @foreach ($estados as $estado)
                                    <a href="{{ route('calidad.mt-gi-11.matriz', array_merge($queryBase, ['estado_id' => $estado['id']])) }}" class="module-tab mt-gi-11-page__estado-pill status-pill--{{ $estado['color'] }} {{ $estadoActivo === $estado['id'] ? 'module-tab--active' : '' }}">{{ $estado['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="mt-gi-11-count">…</strong>
                            <span>registro(s)</span>
                            @unless ($viewAll)
                                <span class="mt-gi-11-page__scope">Solo mis solicitudes</span>
                            @endunless
                        </p>

                        <div class="cursos-registros-page__table-actions">
                            <x-export-excel
                                route="{{ $exportUrl }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar a Excel (respeta filtros)"
                                aria-label="Exportar a Excel"
                            />
                            @if ($canCreate)
                                <a href="{{ route('calidad.mt-gi-11.matriz.create') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar solicitud" aria-label="Agregar solicitud">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </a>
                            @endif
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Plantilla e importar (masivos)"
                                    aria-label="Plantilla e importar"
                                    x-on:click.prevent="$dispatch('open-modal', 'mt-gi-11-masivos')"
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            class="data-table js-mt-gi-11-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                            aria-label="Matriz MT-GI-11"
                        >
                            <thead>
                                <tr>
                                    <th>Fecha solicitud</th>
                                    <th>Consecutivo</th>
                                    <th>Proceso</th>
                                    <th>Tipo de documento</th>
                                    <th>Nombre del documento</th>
                                    <th>Tipo de solicitud</th>
                                    <th>Código actual</th>
                                    <th>Versión actual</th>
                                    <th>Responsable</th>
                                    <th>Cargo del responsable</th>
                                    <th>Código estandarización</th>
                                    <th>Fecha estandarización</th>
                                    <th>Versión actualizada</th>
                                    <th>Estado</th>
                                    <th>Usuario que solicitó</th>
                                    <th>Adjuntos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal masivos: plantilla, importación y export --}}
        @if ($canEdit)
            <x-modal name="mt-gi-11-masivos" maxWidth="lg" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-upload width="18" height="18" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title">Masivos</h3>
                                <p class="ficha-empleados-masivos-modal__lead">Importe solicitudes por Excel o exporte el listado filtrado.</p>
                            </div>
                        </div>
                        <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'mt-gi-11-masivos')">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="ficha-empleados-masivos-modal__cards">
                        <section class="ficha-empleados-masivos-modal__card">
                            <div class="ficha-empleados-masivos-modal__card-head">
                                <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--export" aria-hidden="true">
                                    <x-lucide-file-spreadsheet width="18" height="18" />
                                </span>
                                <div>
                                    <h4 class="ficha-empleados-masivos-modal__card-title">Exportar</h4>
                                    <p class="ficha-empleados-masivos-modal__card-note">Respeta los filtros de la pantalla. Incluye el ID de cada solicitud.</p>
                                </div>
                            </div>
                            <div class="ficha-empleados-masivos-modal__export">
                                <a href="{{ $exportUrl }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                                    <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                                    Exportar listado
                                </a>
                            </div>
                        </section>

                        <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                            <div class="ficha-empleados-masivos-modal__card-head">
                                <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                                    <x-lucide-upload width="18" height="18" />
                                </span>
                                <div>
                                    <h4 class="ficha-empleados-masivos-modal__card-title">Importar</h4>
                                    <p class="ficha-empleados-masivos-modal__card-note">El consecutivo puede repetirse: se usa para identificar versiones del mismo documento. Los catálogos se escriben por nombre.</p>
                                </div>
                            </div>
                            <div class="ficha-empleados-masivos-modal__import">
                                <a href="{{ $importTemplateUrl }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                                    <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                                    Descargar plantilla
                                </a>

                                <form method="POST" action="{{ $importUrl }}" enctype="multipart/form-data" class="ficha-empleados-masivos-modal__import-form" data-mt-gi-11-import-form>
                                    @csrf
                                    {{-- Input oculto: se dispara desde la etiqueta "Elegir archivo". --}}
                                    <input
                                        type="file"
                                        id="mt-gi-11-import-file"
                                        name="import_file"
                                        accept=".xlsx,.xls,.csv"
                                        class="ficha-empleados-masivos-modal__file-input"
                                        data-mt-gi-11-import-file
                                        required
                                        hidden
                                    >
                                    <span class="ficha-empleados-masivos-modal__file-name" data-mt-gi-11-import-name>Sin archivo seleccionado</span>
                                    <div class="ficha-empleados-masivos-modal__import-actions">
                                        <label
                                            for="mt-gi-11-import-file"
                                            class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
                                            data-mt-gi-11-import-choose
                                        >
                                            <x-lucide-file-up width="15" height="15" aria-hidden="true" />
                                            Elegir archivo
                                        </label>
                                        <button
                                            type="submit"
                                            class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary"
                                            data-mt-gi-11-import-submit
                                            disabled
                                        >
                                            <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                            Importar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </x-modal>
        @endif

        {{-- Confirmación de eliminación --}}
        <div class="cursos-registros-page__modal" x-show="deleteOpen" x-cloak @keydown.escape.window="deleteOpen = false">
            <div class="cursos-registros-page__modal-backdrop" @click="deleteOpen = false"></div>
            <div class="modal-card ficha-empleados-masivos-modal" role="dialog" aria-modal="true" aria-labelledby="mt-gi-11-delete-title">
                <div class="ficha-empleados-masivos-modal__header">
                    <div>
                        <h3 id="mt-gi-11-delete-title" class="ficha-empleados-masivos-modal__title">Eliminar solicitud <span x-text="deleteLabel"></span></h3>
                        <p class="ficha-empleados-masivos-modal__lead">Se eliminan la solicitud y sus archivos adjuntos. La acción queda en el log de auditoría.</p>
                    </div>
                    <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" @click="deleteOpen = false">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <form method="POST" :action="deleteUrl" class="ficha-empleados-masivos-modal__footer">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn--secondary" @click="deleteOpen = false">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Eliminar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $ = window.jQuery;
            if (! $ || ! $.fn.DataTable) {
                return;
            }

            const $table = $('.js-mt-gi-11-datatable');
            if (! $table.length) {
                return;
            }

            // Quita el estado de carga del wrapper. Se llama al inicializar, al
            // recibir el XHR y como red de seguridad: la tabla nunca queda oculta.
            const $wrap = $table.closest('.data-table-wrap');
            const revealTable = function () {
                $wrap.removeClass('data-table-wrap--booting');
                $wrap.find('.data-table-wrap__loader').attr('aria-busy', 'false');
            };

            const api = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: $table.data('dt-url') },
                // Traducción embebida: evita la descarga del CDN en cada carga
                // (era la causa de la demora en mostrar la tabla).
                language: {
                    emptyTable: 'No hay solicitudes para este filtro.',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros en total)',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    loadingRecords: 'Cargando…',
                    processing: 'Procesando…',
                    search: 'Buscar:',
                    zeroRecords: 'No se encontraron resultados',
                    paginate: {
                        first: 'Primero',
                        last: 'Último',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                },
                dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                pageLength: 25,
                responsive: false,
                order: [[0, 'desc']],
                columnDefs: [{ targets: [16], orderable: false, searchable: false }],
            });

            // La estructura ya está montada: se libera el wrapper sin esperar al XHR.
            revealTable();

            // El contador usa el total filtrado del servidor, no las filas visibles.
            api.on('xhr.dt', function (_event, _settings, json) {
                revealTable();
                const el = document.getElementById('mt-gi-11-count');
                if (el && json && typeof json.recordsFiltered !== 'undefined') {
                    el.textContent = json.recordsFiltered;
                }
            });

            // Red de seguridad: si el XHR no responde, la tabla se muestra igual.
            window.setTimeout(revealTable, 4000);

            // DataTables inyecta HTML: los eventos se delegan.
            $table.on('click', '.js-mt-gi-11-delete', function () {
                window.dispatchEvent(new CustomEvent('mt-gi-11-delete', {
                    detail: {
                        url: this.dataset.deleteUrl,
                        label: this.dataset.deleteLabel,
                    },
                }));
            });
        });

        // Import masivo: el input real está oculto y se dispara desde la etiqueta.
        // Al elegir archivo se muestra el nombre y se habilita el botón de importar.
        document.addEventListener('change', function (event) {
            const input = event.target;

            if (! input.matches('[data-mt-gi-11-import-file]')) {
                return;
            }

            const form = input.closest('[data-mt-gi-11-import-form]');
            if (! form) {
                return;
            }

            const nameEl = form.querySelector('[data-mt-gi-11-import-name]');
            const submitEl = form.querySelector('[data-mt-gi-11-import-submit]');
            const file = input.files && input.files[0];

            if (nameEl) {
                nameEl.textContent = file ? file.name : 'Sin archivo seleccionado';
            }

            if (submitEl) {
                submitEl.disabled = ! file;
            }
        });
    </script>
</x-app-layout>
