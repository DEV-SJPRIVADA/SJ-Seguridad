<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['client_id'] ?? null)
            || ($filters['city_id'] ?? null)
            || ($filters['mine_only'] ?? false)
            || ($filters['date_from'] ?? null)
            || ($filters['date_to'] ?? null);

        $hasDateFilters = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null);

        $trackingQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
            'client_id' => array_key_exists('client_id', $overrides) ? $overrides['client_id'] : ($filters['client_id'] ?: null),
            'city_id' => array_key_exists('city_id', $overrides) ? $overrides['city_id'] : ($filters['city_id'] ?: null),
            'date_from' => array_key_exists('date_from', $overrides) ? $overrides['date_from'] : ($filters['date_from'] ?: null),
            'date_to' => array_key_exists('date_to', $overrides) ? $overrides['date_to'] : ($filters['date_to'] ?: null),
            'mine_only' => array_key_exists('mine_only', $overrides)
                ? ($overrides['mine_only'] ? '1' : null)
                : (($filters['mine_only'] ?? false) ? '1' : null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Mis requisiciones</h3>
                    <p class="panel-text panel-text--compact">Consulta de solicitudes del area actual con filtros de estado, cliente y vista rapida de tus propios requerimientos.</p>
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

                                    <div class="req-manage-filters__query-row">
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--search">
                                            <label class="req-manage-filters__label" for="tracking-search-input">Buscar</label>
                                            <input
                                                id="tracking-search-input"
                                                type="search"
                                                name="q"
                                                class="form-input"
                                                value="{{ $filters['q'] }}"
                                                placeholder="Codigo, solicitante, cargo, cliente..."
                                            >
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="tracking-date-from">Desde</label>
                                            <input type="date" id="tracking-date-from" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="tracking-date-to">Hasta</label>
                                            <input type="date" id="tracking-date-to" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}">
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
                                <form method="GET" id="tracking-advanced-form" class="req-manage-filters__row" onchange="this.submit()">
                                    @if ($filters['q'] ?? '')
                                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                    @endif
                                    @if ($filters['status'] ?? '')
                                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                    @endif
                                    @if ($filters['date_from'] ?? null)
                                        <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                                    @endif
                                    @if ($filters['date_to'] ?? null)
                                        <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                                    @endif

                                    <div class="req-manage-filters__field">
                                        <label class="req-manage-filters__label" for="tracking-client-select">Cliente</label>
                                        <x-searchable-select
                                            id="tracking-client-select"
                                            name="client_id"
                                            :options="$catalogs['clients']"
                                            :value="$filters['client_id'] ?? ''"
                                            placeholder="Todos los clientes"
                                            searchPlaceholder="Buscar cliente…"
                                        />
                                    </div>

                                    <div class="req-manage-filters__field">
                                        <label class="req-manage-filters__label" for="tracking-city-select">Ciudad</label>
                                        <x-searchable-select
                                            id="tracking-city-select"
                                            name="city_id"
                                            :options="$catalogs['cities']"
                                            :value="$filters['city_id'] ?? ''"
                                            placeholder="Todas las ciudades"
                                            searchPlaceholder="Buscar ciudad…"
                                        />
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

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact" title="{{ number_format($requisitions->total()) }} {{ $requisitions->total() === 1 ? 'requisicion encontrada' : 'requisiciones encontradas' }}">
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
                                @if ($hasDateFilters)
                                    · Fecha solicitud:
                                    <strong>{{ $filters['date_from'] ?? '…' }}</strong>
                                    —
                                    <strong>{{ $filters['date_to'] ?? '…' }}</strong>
                                @endif
                                · El Excel exporta el detalle completo segun estos filtros
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="data-table js-datatable"
                            style="width:100%"
                            data-order='[[1, "desc"]]'
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            data-server-pagination
                        >
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
                                        <td><x-date-table :value="$requisition->request_date" /></td>
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
                                            @php
                                                $rejectionComment = $requisition->managementRejectionComment();
                                            @endphp
                                            <span
                                                class="status-pill status-pill--req-{{ $requisition->status }}"
                                                @if ($rejectionComment) title="Observacion de gerencia: {{ $rejectionComment }}" @endif
                                            >
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                        <td><x-date-table :value="$requisition->status_changed_at" datetime empty="Sin cambios" /></td>
                                        <td class="table-actions">
                                            <a href="{{ route('requisitions.tracking.show', ['module' => $moduleKey, 'requisition' => $requisition]) }}" class="btn btn--secondary btn--sm">
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

                    @if ($requisitions->hasPages())
                        <div class="req-manage-shell__pagination pagination-wrap">
                            {{ $requisitions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
