<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Servicios</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">Comercial — contratos y portafolios vinculados a clientes</p>
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
                        <h3 class="panel-title">Listado de servicios</h3>
                        <p class="panel-text">Cada servicio pertenece a un cliente. Filtre por contrato, asesor, NIT o portafolio.</p>
                    </div>
                    @if ($canManage)
                        <a href="{{ route('comercial.matriz.services.create') }}" class="btn btn--primary">Nuevo servicio</a>
                    @endif
                </div>

                <div class="panel__body">
                    <form method="GET" class="permission-filter-bar bottom-spaced">
                        <input type="search" name="q" class="form-input permission-filter-bar__search" value="{{ $filters['q'] }}" placeholder="Cliente, NIT, contrato o asesor">
                        <select name="portfolio" class="form-select permission-filter-bar__select">
                            <option value="">Todos los portafolios</option>
                            @foreach ($portfolios as $key => $label)
                                <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="vigencia" class="form-select permission-filter-bar__select">
                            <option value="">Toda vigencia</option>
                            <option value="expiring" @selected(($filters['vigencia'] ?? '') === 'expiring')>Por vencer ≤30 dias</option>
                            <option value="expired" @selected(($filters['vigencia'] ?? '') === 'expired')>Vencidos</option>
                        </select>
                        <button type="submit" class="btn btn--secondary">Filtrar</button>
                    </form>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>NIT</th>
                                    <th>Portafolio</th>
                                    <th>Contrato</th>
                                    <th>Tipo</th>
                                    <th>Asesor</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Vigencia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($services as $service)
                                    <tr>
                                        <td>
                                            @if ($service->client)
                                                <a href="{{ route('comercial.matriz.clients.show', $service->client) }}">{{ $service->client->name }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $service->client?->nit ?: '—' }}</td>
                                        <td>{{ $portfolios[$service->portfolio] ?? $service->portfolio }}</td>
                                        <td>{{ $service->contract_number ?: '—' }}</td>
                                        <td>{{ $service->serviceType?->name ?: '—' }}</td>
                                        <td>{{ $service->advisor_name ?: '—' }}</td>
                                        <td>{{ $service->contract_start?->format('Y-m-d') ?: '—' }}</td>
                                        <td>{{ $service->contract_end?->format('Y-m-d') ?: '—' }}</td>
                                        <td>
                                            @if ($service->isExpired())
                                                <span class="status-pill status-pill--req-cancelada">Vencido</span>
                                            @elseif ($service->isExpiringSoon(30))
                                                <span class="status-pill status-pill--req-en_gestion">≤30 dias</span>
                                            @elseif ($service->isExpiringSoon(60))
                                                <span class="status-pill status-pill--req-solicitada">≤60 dias</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            @if ($canManage)
                                                <a href="{{ route('comercial.matriz.services.edit', $service) }}" class="btn btn--secondary btn--sm">Editar</a>
                                                @if ($service->portfolio !== \App\Models\CommercialService::PORTFOLIO_INACTIVOS)
                                                    <form method="POST" action="{{ route('comercial.matriz.services.inactivate', $service) }}" style="display:inline;" onsubmit="return confirm('¿Mover este servicio a Inactivos?');">
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

                    <!-- DataTables maneja paginacion y selector de filas -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
