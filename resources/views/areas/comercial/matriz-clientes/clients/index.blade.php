<x-app-layout>
    <x-slot name="header">
        <div class="app-container comercial-clients-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Clientes</h2>
                <p class="panel-text">Comercial — maestro de clientes (NIT)</p>
            </div>
        </div>
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

    <div class="page-section comercial-clients-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success comercial-clients-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel comercial-clients-panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters comercial-clients-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Listado de clientes</h3>
                                <p class="panel-text">NIT, nombre o ciudad · varios servicios por cliente</p>
                            </div>
                            <div class="req-manage-filters__actions comercial-clients-filters__actions">
                                <x-export-excel route="{{ route('comercial.matriz.clients.export', request()->query()) }}" />
                                @if ($hasActiveFilters)
                                    <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                @endif
                                <a href="{{ route('comercial.matriz.clients.checklist.index') }}" class="btn btn--secondary btn--sm">Checklist</a>
                                @if ($canManage)
                                    <a href="{{ route('comercial.matriz.clients.create') }}" class="btn btn--primary btn--sm">Nuevo cliente</a>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar comercial-clients-filters__toolbar">
                            <form method="GET" class="comercial-clients-filters__form req-manage-filters__search-col ">
                                @if ($filters['status'] ?? '')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="clients-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group comercial-clients-filters__search-group">
                                    <input
                                        id="clients-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="NIT, nombre o representante"
                                    >
                                    <input
                                        id="clients-city-input"
                                        type="search"
                                        name="city"
                                        class="form-input comercial-clients-filters__city"
                                        value="{{ $filters['city'] }}"
                                        placeholder="Ciudad"
                                    >
                                    <button type="submit" class="btn btn--primary">Buscar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col comercial-clients-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills">
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

                        <p class="req-manage-filters__meta comercial-clients-filters__meta">
                            <strong>{{ number_format($clients->count()) }}</strong>
                            {{ $clients->count() === 1 ? 'cliente' : 'clientes' }}
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                            @if ($filters['city'] ?? '')
                                · Ciudad: <strong>{{ $filters['city'] }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="data-table-wrap comercial-clients-page__table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
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
                                            @if ($client->active_services_count > 0)
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
        </div>
    </div>
</x-app-layout>
