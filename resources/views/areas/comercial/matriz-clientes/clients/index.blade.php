<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Matriz de clientes (MT-CO-01)</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">Comercial — clientes y servicios por portafolio</p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <div class="panel">
                <div class="panel__header" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h3 class="panel-title">Clientes</h3>
                        <p class="panel-text">Busque por NIT, nombre o ciudad. Cada cliente puede tener varios servicios.</p>
                    </div>
                    @if ($canManage)
                        <a href="{{ route('comercial.matriz.clients.create') }}" class="btn btn--primary">Nuevo cliente</a>
                    @endif
                </div>

                <div class="panel__body">
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
                                    <th>Tipos de servicio</th>
                                    <th>Servicios</th>
                                    <th>Activos</th>
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
                                        <td>{{ $client->active_services_count }}</td>
                                        <td class="table-actions">
                                            <a href="{{ route('comercial.matriz.clients.show', $client) }}" class="btn btn--secondary btn--sm">Abrir</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">No hay clientes registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-wrap top-spaced">
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
