<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem; display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <h2 class="panel-title" style="margin:0;">{{ $client->name }}</h2>
                <p class="panel-text" style="margin:0.25rem 0 0;">NIT {{ $client->nit }} · {{ $client->city ?: 'Sin ciudad' }}</p>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary btn--sm">Listado</a>
                @if ($canManage)
                    <a href="{{ route('comercial.matriz.clients.edit', $client) }}" class="btn btn--secondary btn--sm">Editar cliente</a>
                    <a href="{{ route('comercial.matriz.services.create', ['client' => $client->id]) }}" class="btn btn--primary btn--sm">Agregar servicio</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <div class="panel bottom-spaced">
                <div class="panel__header">
                    <h3 class="panel-title">Datos del cliente</h3>
                </div>
                <div class="panel__body" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                    <div><strong>Telefono</strong><br>{{ $client->phone ?: '—' }}</div>
                    <div><strong>Direccion</strong><br>{{ $client->address ?: '—' }}</div>
                    <div><strong>R. Legal</strong><br>{{ $client->legal_rep_name ?: '—' }}</div>
                    <div><strong>Doc. RL</strong><br>{{ $client->legal_rep_doc ?: '—' }}</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Servicios / contratos</h3>
                    <p class="panel-text">Un cliente puede tener lineas en distintos portafolios.</p>
                </div>
                <div class="panel__body">
                    <form method="GET" class="permission-filter-bar bottom-spaced">
                        <select name="portfolio" class="form-select permission-filter-bar__select">
                            <option value="">Todos los portafolios</option>
                            @foreach ($portfolios as $key => $label)
                                <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn--secondary">Filtrar</button>
                    </form>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" style="width:100%">
                            <thead>
                                <tr>
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
                                        <td colspan="8">Sin servicios{{ $filters['portfolio'] ? ' en este portafolio' : '' }}.</td>
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
