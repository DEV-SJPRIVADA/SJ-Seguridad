<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.catalogos-subnav', ['subTabs' => $subTabs])
    </x-slot>
    @php
        // Chips de estado: Activos es el default (sin parámetro en la URL).
        $estadoChips = collect($estados)->map(fn (string $label, string $value): array => [
            'label' => $label,
            'url' => route('purchase-requests.providers.index', array_merge(
                ['module' => $module],
                $value === \App\Models\PurchaseProvider::ESTADO_ACTIVO ? [] : ['estado' => $value],
            )),
            'active' => $filters['estado'] === $value,
        ]);
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

            <div class="panel purchase-providers-page__panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    {{-- Franja única: chips de estado + conteo (izquierda) y acciones icon-only (derecha).
                         El buscador global vive en la tabla (DataTables). --}}
                    <div class="purchase-providers-page__toolbar">
                        <div class="purchase-providers-page__toolbar-start">
                            <nav class="cursos-registros-page__status-chips" aria-label="Filtrar por estado">
                                @foreach ($estadoChips as $chip)
                                    <a
                                        href="{{ $chip['url'] }}"
                                        class="module-tab {{ $chip['active'] ? 'module-tab--active' : '' }}"
                                        @if ($chip['active']) aria-current="page" @endif
                                    >{{ $chip['label'] }}</a>
                                @endforeach
                            </nav>
                            <p class="req-manage-filters__meta purchase-providers-page__count">
                                <strong id="providers-count">…</strong>
                                <span id="providers-count-label">proveedores</span>
                            </p>
                        </div>

                        <div class="cursos-registros-page__table-actions purchase-providers-page__table-actions">
                            <x-export-excel
                                id="providers-export-link"
                                route="{{ $exportUrl }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar Excel"
                                aria-label="Exportar el catálogo a Excel"
                            />
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
                                <a
                                    href="{{ $createUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nuevo proveedor"
                                    aria-label="Nuevo proveedor"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table purchase-providers-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="providers-datatable"
                            class="data-table"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>IDENTIFICACIÓN</th>
                                    <th>NOMBRE / RAZÓN SOCIAL</th>
                                    <th>TELÉFONO</th>
                                    <th>CORREO</th>
                                    <th>CONTACTO</th>
                                    <th>CIUDAD</th>
                                    <th>DIRECCIÓN</th>
                                    <th class="purchase-providers-page__col-center">DÍAS PAGO</th>
                                    <th class="purchase-providers-page__col-center">ESTADO</th>
                                    <th class="purchase-providers-page__col-actions">ACCIONES</th>
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
                    // Presentación por columna: ID sin cortes, nombre destacado, correo enlazable, dirección recortada, días y acciones centrados.
                    columnDefs: [
                        { targets: [0], className: 'purchase-providers-page__col-id' },
                        { targets: [1], className: 'purchase-providers-page__col-name' },
                        {
                            targets: [3],
                            render(data, type) {
                                if (type !== 'display' || ! data || data === '—') {
                                    return data;
                                }

                                const email = $('<textarea>').html(data).text();

                                return $('<a>', {
                                    href: `mailto:${email}`,
                                    class: 'purchase-providers-page__email',
                                    text: email,
                                })[0].outerHTML;
                            },
                        },
                        { targets: [6], className: 'purchase-providers-page__col-address' },
                        { targets: [7], className: 'purchase-providers-page__col-center' },
                        { targets: [8], className: 'purchase-providers-page__col-center' },
                        {
                            targets: [9],
                            orderable: false,
                            searchable: false,
                            className: 'purchase-providers-page__col-actions',
                        },
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
