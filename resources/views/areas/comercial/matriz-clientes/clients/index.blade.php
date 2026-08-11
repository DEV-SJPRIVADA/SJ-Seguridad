<x-app-layout>
    <x-slot name="header">
        @include('areas.comercial.partials.gestion-clientes-subnav', ['subTabs' => $subTabs])

    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['city'] ?? '') !== ''
            || ($filters['status'] ?? '') !== '';

        $clientsQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'city' => array_key_exists('city', $overrides) ? $overrides['city'] : ($filters['city'] ?: null),
            'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section comercial-clients-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success comercial-clients-page__alert">{{ session('status') }}</div>
            @endif

            @include('partials.import-failure-report', [
                'importResult' => session('import_result'),
                'downloadRoute' => 'comercial.matriz.clients.import-report',
            ])

            <div class="panel comercial-clients-panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Listado de clientes</h3>
                    <p class="panel-text panel-text--compact">NIT, nombre o ciudad · varios servicios por cliente</p>
                </div>

                <div class="panel__body req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>

                        <div class="req-manage-filters__panel-body">
                            <div class="req-manage-filters__head">
                                <div class="req-manage-filters__actions comercial-clients-filters__actions">
                                    <x-export-excel route="{{ route('comercial.matriz.clients.export', request()->query()) }}" />
                                    @if ($canManage)
                                        <button
                                            type="button"
                                            class="btn btn--secondary btn--sm"
                                            title="Carga masiva"
                                            aria-label="Carga masiva"
                                            x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'comercial-masivos')"
                                        >
                                            <x-lucide-icon name="upload" :size="16" />
                                        </button>
                                    @endif
                                    @if ($hasActiveFilters)
                                        <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                    @endif
                                    <a href="{{ route('comercial.matriz.clients.checklist.index') }}" class="btn btn--secondary btn--sm">Checklist</a>
                                    @if ($canManage)
                                        <a href="{{ route('comercial.matriz.clients.create') }}" class="btn btn--primary btn--sm">Nuevo cliente</a>
                                    @endif
                                </div>
                            </div>

                            <div class="req-manage-filters__toolbar">
                                <form method="GET" class="req-manage-filters__search-col">
                                    @if ($filters['status'] ?? '')
                                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                    @endif

                                    <div class="req-manage-filters__query-row">
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--search">
                                            <label class="req-manage-filters__label" for="clients-search-input">Buscar</label>
                                            <input
                                                id="clients-search-input"
                                                type="search"
                                                name="q"
                                                class="form-input"
                                                value="{{ $filters['q'] }}"
                                                placeholder="NIT, nombre o representante"
                                            >
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="clients-city-input">Ciudad</label>
                                            <input
                                                id="clients-city-input"
                                                type="search"
                                                name="city"
                                                class="form-input"
                                                value="{{ $filters['city'] }}"
                                                placeholder="Ciudad"
                                            >
                                        </div>
                                        <div class="req-manage-filters__query-submit">
                                            <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                            <button type="submit" class="btn btn--primary">Buscar</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="req-manage-filters__status-row">
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                        <a
                                            href="{{ route('comercial.matriz.clients.index', $clientsQuery(['status' => null])) }}"
                                            class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                        >Todos</a>
                                        @foreach ($statusLabels as $statusKey => $statusLabel)
                                            <a
                                                href="{{ route('comercial.matriz.clients.index', $clientsQuery(['status' => $statusKey])) }}"
                                                class="req-manage-filters__pill {{ $statusKey === 'active' ? 'status-pill--success' : 'status-pill--danger' }} {{ ($filters['status'] ?? '') === $statusKey ? 'is-active' : '' }}"
                                            >{{ $statusLabel }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact" title="{{ number_format($clients->count()) }} {{ $clients->count() === 1 ? 'cliente' : 'clientes' }}">
                                <strong>{{ number_format($clients->count()) }}</strong>
                                {{ $clients->count() === 1 ? 'cliente' : 'clientes' }}
                                @if ($filters['q'] ?? '')
                                    · Busqueda: <strong>{{ $filters['q'] }}</strong>
                                @endif
                                @if ($filters['city'] ?? '')
                                    · Ciudad: <strong>{{ $filters['city'] }}</strong>
                                @endif
                                @if ($filters['status'] ?? '')
                                    · Estado: <strong>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</strong>
                                @endif
                                · El Excel exporta el detalle completo segun estos filtros
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table comercial-clients-page__table-wrap">
                        <table
                            class="data-table js-datatable"
                            style="width:100%"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>NIT</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Portafolio</th>
                                    <th>Tipos de servicio</th>
                                    <th>Servicios</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    <tr>
                                        <td>{{ $client->nit }}</td>
                                        <td>{{ $client->name }}</td>
                                        <td>{{ $client->city ?: '—' }}</td>
                                        <td class="client-tags-cell">
                                            @if (! empty($client->portfolio_labels))
                                                <div class="client-tag-list">
                                                    @foreach ($client->portfolio_labels as $portfolioLabel)
                                                        <span class="status-pill status-pill--muted">{{ $portfolioLabel }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="client-tags-cell">
                                            @if (! empty($client->service_type_labels))
                                                <div class="client-tag-list">
                                                    @foreach ($client->service_type_labels as $typeLabel)
                                                        <span class="status-pill status-pill--muted">{{ $typeLabel }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $client->services_count }}</td>
                                        <td>
                                            @if ($client->active_operational_services_count > 0)
                                                <span class="status-pill status-pill--success">Activo</span>
                                            @else
                                                <span class="status-pill status-pill--danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('comercial.matriz.clients.show', $client) }}" class="btn btn--secondary btn--sm">Abrir</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">No hay clientes registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canManage)
                @include('areas.comercial.matriz-clientes.partials.masivos-modal', [
                    'filters' => $filters,
                    'canManage' => $canManage,
                    'show' => $errors->has('export') || $errors->has('import_file') || (is_array(session('import_result')) && ((session('import_result.failures_count') ?? 0) > 0 || session('import_result.report_token'))),
                ])
            @endif
        </div>
    </div>

    @if ($canManage)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var fileInput = document.querySelector('[data-comercial-import-file]');
                    var fileName = document.querySelector('[data-comercial-import-name]');
                    var submitBtn = document.querySelector('[data-comercial-import-submit]');
                    var form = document.querySelector('[data-comercial-import-form]');
                    var loading = document.querySelector('[data-comercial-import-loading]');

                    if (fileInput && fileName && submitBtn) {
                        fileInput.addEventListener('change', function () {
                            var name = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : 'Sin archivo seleccionado';
                            fileName.textContent = name;
                            submitBtn.disabled = !fileInput.files || fileInput.files.length === 0;
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
    @endif
</x-app-layout>
