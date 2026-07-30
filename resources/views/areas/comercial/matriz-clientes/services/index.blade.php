<x-app-layout>
    <x-slot name="header">
        @include('areas.comercial.partials.gestion-clientes-subnav', ['subTabs' => $subTabs])
        <div class="app-container comercial-services-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Servicios</h2>
                <p class="panel-text">Comercial — contratos y portafolios vinculados a clientes</p>
            </div>
        </div>
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

    <div class="page-section comercial-services-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success comercial-services-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel comercial-services-panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters comercial-services-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Listado de servicios</h3>
                                <p class="panel-text">Contrato, asesor, NIT o portafolio · cada servicio pertenece a un cliente</p>
                            </div>
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

                        <div class="req-manage-filters__toolbar comercial-services-filters__toolbar">
                            <form method="GET" class="comercial-services-filters__form comercial-services-filters__search-col req-manage-filters__search-col">
                                @if ($filters['status'] ?? '')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="services-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group comercial-services-filters__search-group">
                                    <input
                                        id="services-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cliente, NIT, contrato o asesor"
                                    >
                                    <select name="portfolio" class="form-select">
                                        <option value="">Todos los portafolios</option>
                                        @foreach ($portfolios as $key => $label)
                                            <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="vigencia" class="form-select form-select--wide">
                                        <option value="">Toda vigencia</option>
                                        <option value="expiring" @selected(($filters['vigencia'] ?? '') === 'expiring')>Por vencer (30 días)</option>
                                        <option value="expired" @selected(($filters['vigencia'] ?? '') === 'expired')>Vencido (contrato)</option>
                                    </select>
                                    <button type="submit" class="btn btn--primary">Filtrar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col comercial-services-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills">
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

                        <p class="req-manage-filters__meta comercial-services-filters__meta">
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
                        </p>
                    </div>

                    <div class="data-table-wrap comercial-services-page__table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
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
