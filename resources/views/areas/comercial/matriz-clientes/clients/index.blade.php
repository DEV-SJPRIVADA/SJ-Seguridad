<x-app-layout>
    <x-slot name="header">
        <div class="app-container comercial-clients-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Clientes</h2>
                <p class="panel-text">Comercial — maestro de clientes (NIT)</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section comercial-clients-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <div class="panel">
                <div class="panel__header panel__header--compact panel__header--split">
                    <div class="panel-heading-row panel-heading-row--wrap">
                        <h3 class="panel-title">Listado de clientes</h3>
                        <p class="panel-text">Busque por NIT, nombre o ciudad. Cada cliente puede tener varios servicios.</p>
                    </div>
                    <div class="panel__header-actions">
                        <x-export-excel route="{{ route('comercial.matriz.clients.export', request()->query()) }}" />
                        @if ($canManage)
                            <a href="{{ route('comercial.matriz.clients.create') }}" class="btn btn--primary">Nuevo cliente</a>
                        @endif
                    </div>
                </div>

                <div class="panel__body">
                    <div class="bottom-spaced" style="padding: 0.2rem;">
                        <a href="{{ route('comercial.matriz.clients.checklist.index') }}" class="btn btn--secondary">Checklist documental</a>
                    </div>

                    <form method="GET" class="permission-filter-bar bottom-spaced">
                        <input type="search" name="q" class="form-input permission-filter-bar__search" value="{{ $filters['q'] }}" placeholder="NIT, nombre o representante">
                        <input type="search" name="city" class="form-input permission-filter-bar__select" value="{{ $filters['city'] }}" placeholder="Ciudad">
                        <button type="submit" class="btn btn--secondary">Filtrar</button>
                    </form> 

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" style="width:100%">
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
                                        <td>
                                            @if (! empty($client->portfolio_labels))
                                                <div style="display:flex; flex-wrap:wrap; gap:0.35rem; justify-content:center;">
                                                    @foreach ($client->portfolio_labels as $portfolioLabel)
                                                        <span class="status-pill status-pill--muted">{{ $portfolioLabel }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if (! empty($client->service_type_labels))
                                                <div style="display:flex; flex-wrap:wrap; gap:0.35rem; justify-content:center;">
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

                    <!-- DataTables maneja paginacion y selector de filas -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
