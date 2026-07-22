<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['client_id'] ?? null)
            || ($filters['city_id'] ?? null)
            || ($filters['mine_only'] ?? false);

        $trackingQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
            'client_id' => array_key_exists('client_id', $overrides) ? $overrides['client_id'] : ($filters['client_id'] ?: null),
            'city_id' => array_key_exists('city_id', $overrides) ? $overrides['city_id'] : ($filters['city_id'] ?: null),
            'mine_only' => array_key_exists('mine_only', $overrides)
                ? ($overrides['mine_only'] ? '1' : null)
                : (($filters['mine_only'] ?? false) ? '1' : null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Mis requisiciones</h3>
                    <p class="panel-text">Consulta de solicitudes del area actual con filtros de estado, cliente y vista rapida de tus propios requerimientos.</p>
                </div>

                <div class="panel__body">
                    <div class="req-manage-filters">
                        <div class="req-manage-filters__head">
                            <h4 class="req-manage-filters__title">Filtros</h4>
                            <div class="req-manage-filters__actions">
                                <x-export-excel route="{{ route('requisitions.tracking.export', ['module' => $moduleKey, ...request()->query()]) }}" />
                                @if ($hasActiveFilters)
                                    <a href="{{ route('requisitions.tracking', ['module' => $moduleKey]) }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar">
                            <form method="GET" id="tracking-search-form" class="req-manage-filters__search-col">
                                @if ($filters['status'] ?? '')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                @if ($filters['client_id'] ?? null)
                                    <input type="hidden" name="client_id" value="{{ $filters['client_id'] }}">
                                @endif
                                @if ($filters['city_id'] ?? null)
                                    <input type="hidden" name="city_id" value="{{ $filters['city_id'] }}">
                                @endif
                                @if ($filters['mine_only'] ?? false)
                                    <input type="hidden" name="mine_only" value="1">
                                @endif

                                <label class="req-manage-filters__label" for="tracking-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group">
                                    <input
                                        id="tracking-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Codigo, solicitante, cargo, cliente..."
                                    >
                                    <button type="submit" class="btn btn--primary">Buscar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills">
                                    <a
                                        href="{{ route('requisitions.tracking', ['module' => $moduleKey, ...$trackingQuery(['status' => null])]) }}"
                                        class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                    >Todos</a>
                                    @foreach ($statusLabels as $statusKey => $statusLabel)
                                        <a
                                            href="{{ route('requisitions.tracking', ['module' => $moduleKey, ...$trackingQuery(['status' => $statusKey])]) }}"
                                            class="req-manage-filters__pill status-pill--req-{{ $statusKey }} {{ ($filters['status'] ?? '') === $statusKey ? 'is-active' : '' }}"
                                        >{{ $statusLabel }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="req-manage-filters__extras">
                            <form method="GET" id="tracking-advanced-form" class="req-manage-filters__row">
                                @if ($filters['q'] ?? '')
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                @endif
                                @if ($filters['status'] ?? '')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif

                                <div class="req-manage-filters__field">
                                    <label class="req-manage-filters__label" for="tracking-client-select">Cliente</label>
                                    <select id="tracking-client-select" name="client_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todos los clientes</option>
                                        @foreach ($catalogs['clients'] as $client)
                                            <option value="{{ $client->id }}" @selected((int) ($filters['client_id'] ?? 0) === $client->id)>{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="req-manage-filters__field">
                                    <label class="req-manage-filters__label" for="tracking-city-select">Ciudad</label>
                                    <select id="tracking-city-select" name="city_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todas las ciudades</option>
                                        @foreach ($catalogs['cities'] as $city)
                                            <option value="{{ $city->id }}" @selected((int) ($filters['city_id'] ?? 0) === $city->id)>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="req-manage-filters__field req-manage-filters__field--compact">
                                    <span class="req-manage-filters__label">Alcance</span>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--start">
                                        <a
                                            href="{{ route('requisitions.tracking', ['module' => $moduleKey, ...$trackingQuery(['mine_only' => false])]) }}"
                                            class="req-manage-filters__pill {{ ! ($filters['mine_only'] ?? false) ? 'is-active' : '' }}"
                                        >Todas</a>
                                        <a
                                            href="{{ route('requisitions.tracking', ['module' => $moduleKey, ...$trackingQuery(['mine_only' => true])]) }}"
                                            class="req-manage-filters__pill {{ ($filters['mine_only'] ?? false) ? 'is-active' : '' }}"
                                        >Solo mis solicitudes</a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <p class="req-manage-filters__meta">
                            <strong>{{ number_format($requisitions->total()) }}</strong>
                            {{ $requisitions->total() === 1 ? 'requisicion encontrada' : 'requisiciones encontradas' }}
                            @if ($filters['status'] ?? '')
                                · Estado: <strong>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</strong>
                            @endif
                            @if ($filters['client_id'] ?? null)
                                · Cliente: <strong>{{ $catalogs['clients']->firstWhere('id', $filters['client_id'])?->name }}</strong>
                            @endif
                            @if ($filters['city_id'] ?? null)
                                · Ciudad: <strong>{{ $catalogs['cities']->firstWhere('id', $filters['city_id'])?->name }}</strong>
                            @endif
                            @if ($filters['mine_only'] ?? false)
                                · <strong>Solo mis solicitudes</strong>
                            @endif
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" data-no-excel data-order='[[1, "desc"]]'>
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
                                @forelse ($requisitions as $requisition)
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
                                            <a href="{{ route('requisitions.print', ['module' => $moduleKey, 'requisition' => $requisition]) }}" target="_blank" class="btn btn--secondary btn--sm">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">No hay requisiciones con los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
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
