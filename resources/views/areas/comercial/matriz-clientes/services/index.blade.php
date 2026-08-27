<x-app-layout>
    <x-slot name="header">
        @include('areas.comercial.partials.gestion-clientes-subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['portfolio'] ?? '') !== ''
            || ($filters['vigencia'] ?? '') !== ''
            || ($filters['status'] ?? '') !== '';

        $servicesQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'portfolio' => array_key_exists('portfolio', $overrides) ? $overrides['portfolio'] : ($filters['portfolio'] ?: null),
            'vigencia' => array_key_exists('vigencia', $overrides) ? $overrides['vigencia'] : ($filters['vigencia'] ?: null),
            'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
        ], fn ($value) => $value !== null && $value !== '');

        $serviceStatusPillClass = fn (string $statusKey): string => match ($statusKey) {
            'activo' => 'status-pill--success',
            'por_vencer' => 'status-pill--warning',
            'vencido' => 'status-pill--danger',
            'inactivo' => 'status-pill--muted',
            default => '',
        };
    @endphp

    <div class="page-section comercial-services-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success comercial-services-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel comercial-services-panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Listado de servicios</h3>
                    <p class="panel-text panel-text--compact">Contrato, asesor, NIT o portafolio · cada servicio pertenece a un cliente</p>
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
                                <div class="req-manage-filters__actions comercial-services-filters__actions">
                                    <x-export-excel route="{{ route('comercial.matriz.services.export', request()->query()) }}" />
                                    @if ($hasActiveFilters)
                                        <a href="{{ route('comercial.matriz.services.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                    @endif
                                    @if ($canManage)
                                        <a href="{{ route('comercial.matriz.services.create') }}" class="btn btn--primary btn--sm">Nuevo servicio</a>
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
                                            <label class="req-manage-filters__label" for="services-search-input">Buscar</label>
                                            <input
                                                id="services-search-input"
                                                type="search"
                                                name="q"
                                                class="form-input"
                                                value="{{ $filters['q'] }}"
                                                placeholder="Cliente, NIT, contrato o asesor"
                                            >
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="services-portfolio-select">Portafolio</label>
                                            <x-searchable-select
                                                id="services-portfolio-select"
                                                name="portfolio"
                                                :options="$portfolios"
                                                :value="$filters['portfolio'] ?? ''"
                                                placeholder="Todos los portafolios"
                                                searchPlaceholder="Buscar portafolio…"
                                            />
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="services-vigencia-select">Vigencia</label>
                                            <x-searchable-select
                                                id="services-vigencia-select"
                                                name="vigencia"
                                                :options="[
                                                    ['value' => 'expiring', 'label' => 'Por vencer (30 días)'],
                                                    ['value' => 'expired', 'label' => 'Vencido'],
                                                ]"
                                                :value="$filters['vigencia'] ?? ''"
                                                placeholder="Todas las vigencias"
                                                searchPlaceholder="Buscar vigencia…"
                                            />
                                        </div>
                                        <div class="req-manage-filters__query-submit">
                                            <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                            <button type="submit" class="btn btn--primary">Filtrar</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="req-manage-filters__status-row">
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                        <a
                                            href="{{ route('comercial.matriz.services.index', $servicesQuery(['status' => null])) }}"
                                            class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                        >Todos</a>
                                        @foreach ($statusLabels as $statusKey => $statusLabel)
                                            <a
                                                href="{{ route('comercial.matriz.services.index', $servicesQuery(['status' => $statusKey])) }}"
                                                class="req-manage-filters__pill {{ $serviceStatusPillClass($statusKey) }} {{ ($filters['status'] ?? '') === $statusKey ? 'is-active' : '' }}"
                                            >{{ $statusLabel }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact" title="{{ number_format($services->count()) }} {{ $services->count() === 1 ? 'servicio' : 'servicios' }}">
                                <strong>{{ number_format($services->count()) }}</strong>
                                {{ $services->count() === 1 ? 'servicio' : 'servicios' }}
                                @if ($filters['q'] ?? '')
                                    · Busqueda: <strong>{{ $filters['q'] }}</strong>
                                @endif
                                @if ($filters['portfolio'] ?? '')
                                    · Portafolio: <strong>{{ $portfolios[$filters['portfolio']] ?? $filters['portfolio'] }}</strong>
                                @endif
                                @if ($filters['vigencia'] ?? '')
                                    · Vigencia: <strong>{{ $filters['vigencia'] === 'expiring' ? 'Por vencer (30 días)' : 'Vencido' }}</strong>
                                @endif
                                @if ($filters['status'] ?? '')
                                    · Estado: <strong>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</strong>
                                @endif
                                · El Excel exporta el detalle completo segun estos filtros
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table comercial-services-page__table-wrap">
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
                                    <th>Contrato</th>
                                    <th>Tipo servicio</th>
                                    <th>Portafolio</th>
                                    <th>Asesor</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($services as $service)
                                    @php
                                        $estadoLabel = $service->serviceEstadoLabel();
                                        $estadoPillClass = match ($estadoLabel) {
                                            \App\Models\CommercialService::ESTADO_INACTIVO => 'status-pill--muted',
                                            \App\Models\CommercialService::ESTADO_VENCIDO => 'status-pill--danger',
                                            \App\Models\CommercialService::ESTADO_POR_VENCER => 'status-pill--warning',
                                            default => 'status-pill--success',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $service->client?->nit ?: '—' }}</td>
                                        <td>
                                            @if ($service->client)
                                                <a href="{{ route('comercial.matriz.clients.show', $service->client) }}">{{ $service->client->name }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $service->contract_number ?: '—' }}</td>
                                        <td>{{ $service->serviceType?->name ?: '—' }}</td>
                                        <td>{{ $portfolios[$service->portfolio] ?? $service->portfolio }}</td>
                                        <td>{{ $service->advisor_name ?: '—' }}</td>
                                        <td><x-date-table :value="$service->contract_start" /></td>
                                        <td><x-date-table :value="$service->contract_end" /></td>
                                        <td>
                                            <span class="status-pill {{ $estadoPillClass }}">{{ $estadoLabel }}</span>
                                        </td>
                                        <td class="table-actions">
                                            @if ($canManage)
                                                <a href="{{ route('comercial.matriz.services.edit', $service) }}" class="btn btn--secondary btn--sm">Editar</a>
                                                @if (! $service->is_active)
                                                    <form method="POST" action="{{ route('comercial.matriz.services.activate', $service) }}" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn--primary btn--sm">Activar</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('comercial.matriz.services.inactivate', $service) }}" style="display:inline;" onsubmit="return confirm('¿Inactivar este servicio?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn--secondary btn--sm">Inactivar</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">No hay servicios registrados.</td>
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
