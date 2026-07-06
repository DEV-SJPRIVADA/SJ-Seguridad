<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Seguimiento de requisiciones</h3>
                    <p class="panel-text">Consulta de solicitudes del area actual con filtros de estado, cliente y vista rapida de tus propios requerimientos.</p>
                </div>

                <div class="panel__body">
                    <form method="GET" class="permission-filter-bar bottom-spaced">
                        <input type="search" name="q" class="form-input permission-filter-bar__search" value="{{ $filters['q'] }}" placeholder="Buscar por codigo, solicitante, cargo o cliente">
                        <select name="status" class="form-select permission-filter-bar__select">
                            <option value="">Todos los estados</option>
                            @foreach ($statusLabels as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected($filters['status'] === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                        <select name="client_id" class="form-select permission-filter-bar__select">
                            <option value="">Todos los clientes</option>
                            @foreach ($catalogs['clients'] as $client)
                                <option value="{{ $client->id }}" @selected((int) $filters['client_id'] === $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <select name="city_id" class="form-select permission-filter-bar__select">
                            <option value="">Todas las ciudades</option>
                            @foreach ($catalogs['cities'] as $city)
                                <option value="{{ $city->id }}" @selected((int) $filters['city_id'] === $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="mine_only" value="1" @checked($filters['mine_only'])>
                            <span>Solo mis solicitudes</span>
                        </label>
                        <button type="submit" class="btn btn--secondary">Filtrar</button>
                    </form>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Cantidad</th>
                                    <th>Estado</th>
                                    <th>Ultima actualizacion</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requisitions as $requisition)
                                    <tr>
                                        <td>{{ $requisition->code }}</td>
                                        <td>{{ $requisition->request_date?->format('Y-m-d') }}</td>
                                        <td>
                                            {{ $requisition->requester?->name ?? $requisition->leader_name }}
                                            @if ($requisition->requested_by === auth()->id())
                                                <span class="status-pill status-pill--info">Mia</span>
                                            @endif
                                        </td>
                                        <td>{{ $requisition->position?->name }}</td>
                                        <td>{{ $requisition->client?->name }}</td>
                                        <td>{{ $requisition->city?->name }}</td>
                                        <td>{{ $requisition->quantity }}</td>
                                        <td>
                                            <span class="status-pill status-pill--req-{{ $requisition->status }}">
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                        <td>{{ $requisition->status_changed_at?->format('Y-m-d H:i') ?? 'Sin cambios' }}</td>
                                        <td class="table-actions">
                                            <a href="{{ route('requisitions.print', ['module' => $moduleKey, 'requisition' => $requisition]) }}" target="_blank" class="btn btn--secondary">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-wrap top-spaced">
                        {{ $requisitions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
