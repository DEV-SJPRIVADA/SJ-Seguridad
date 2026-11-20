<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => $matrix->name])
    </x-slot>

    <div class="page-section matrices-page matrices-records">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger bottom-spaced" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="matrices-alert-line">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <header class="matrices-hero matrices-hero--compact">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">{{ $matrix->code ?? 'Matriz' }}</p>
                    <h1 class="matrices-hero__title">{{ $matrix->name }}</h1>
                    <p class="matrices-hero__text">
                        {{ $matrix->description ?: 'Consulta, filtra y exporta los registros de esta matriz.' }}
                    </p>
                </div>

                <div class="matrices-hero__actions">
                    <x-export-excel :route="$exportUrl" label="Excel" class="btn btn--secondary btn--sm" />

                    @if ($canRegister)
                        <a href="{{ route('matrices.submissions.create', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--primary">
                            Registrar
                        </a>
                    @endif
                </div>
            </header>

            <div class="panel matrices-records__panel">
                <div class="panel__body panel__body--compact">
                    <details class="req-manage-filters req-manage-filters__panel matrices-records__filters" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>

                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="req-manage-filters">
                                <div class="matrices-records__filter-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="filter-q">Buscar</label>
                                        <input id="filter-q" type="search" class="form-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Folio o usuario">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="filter-status">Estado</label>
                                        <x-searchable-select
                                            id="filter-status"
                                            name="status"
                                            :options="[
                                                ['value' => 'submitted', 'label' => 'Enviado'],
                                                ['value' => 'draft', 'label' => 'Borrador'],
                                                ['value' => 'archived', 'label' => 'Archivado'],
                                            ]"
                                            :value="$filters['status'] ?? ''"
                                            placeholder="Todos"
                                            searchPlaceholder="Buscar estadoâ€¦"
                                            :allowClear="true"
                                        />
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="filter-from">Desde</label>
                                        <input id="filter-from" type="date" class="form-input" name="from" value="{{ $filters['from'] ?? '' }}">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="filter-to">Hasta</label>
                                        <input id="filter-to" type="date" class="form-input" name="to" value="{{ $filters['to'] ?? '' }}">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="filter-assignee">Asignado a</label>
                                        <x-searchable-select
                                            id="filter-assignee"
                                            name="assignee"
                                            :options="collect($assigneeOptions)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->prepend(['value' => 'me', 'label' => 'Solo mis registros'])->prepend(['value' => 'none', 'label' => 'Sin asignar'])->values()->all()"
                                            :value="$filters['assignee'] ?? ''"
                                            placeholder="Todos"
                                            searchPlaceholder="Buscar usuarioâ€¦"
                                            :allowClear="true"
                                        />
                                    </div>

                                    @foreach ($filterFields as $field)
                                        <div class="form-field">
                                            <label class="form-label" for="filter-field-{{ $field->field_key }}">{{ $field->label }}</label>
                                            <x-searchable-select
                                                id="filter-field-{{ $field->field_key }}"
                                                name="fields[{{ $field->field_key }}]"
                                                :options="collect($field->options ?? [])->map(fn ($option) => ['value' => (string) ($option['key'] ?? ''), 'label' => (string) ($option['label'] ?? '')])->values()->all()"
                                                :value="$filters['fields'][$field->field_key] ?? ''"
                                                placeholder="Todos"
                                                searchPlaceholder="Buscar opciÃ³nâ€¦"
                                                :allowClear="true"
                                            />
                                        </div>
                                    @endforeach
                                </div>

                                <div class="req-manage-filters__actions">
                                    <a href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">Limpiar</a>
                                    <button type="submit" class="btn btn--primary btn--sm">Aplicar filtros</button>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="data-table-wrap matrices-records__table-wrap">
                        <table class="supply-table matrices-records__table js-datatable" style="width:100%" data-dt-responsive="false" data-dt-compact="true" data-dt-url="{{ $datatableUrl }}">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Estado</th>
                                    <th>Registrado por</th>
                                    <th>Asignado a</th>
                                    <th>Fecha</th>
                                    @foreach ($tableFields as $field)
                                        <th>{{ $field->label }}</th>
                                    @endforeach
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
            document.addEventListener('DOMContentLoaded', function () {
                const $table = jQuery('.matrices-records__table');

                if (! $table.length || ! jQuery.fn.DataTable) {
                    return;
                }

                const orderableColumns = {{ 5 + $tableFields->count() }};

                $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                    },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay registros para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[4, 'desc']],
                    columnDefs: [
                        { targets: [orderableColumns], orderable: false, searchable: false },
                    ],
                });
            });
        </script>
    @endpush
</x-app-layout>
