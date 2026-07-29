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
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <x-export-excel route="{{ route('comercial.matriz.services.export', request()->query()) }}" />
                        @if ($canManage)
                            <a href="{{ route('comercial.matriz.services.create') }}" class="btn btn--primary">Nuevo servicio</a>
                        @endif
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" class="services-filters bottom-spaced">
                        <div class="services-filters__inner">
                            <div class="services-filters__field services-filters__field--search">
                                <svg class="services-filters__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="search" name="q" class="services-filters__input" value="{{ $filters['q'] }}" placeholder="Buscar cliente, NIT, contrato o asesor">
                            </div>
                            <div class="services-filters__field">
                                <select name="portfolio" class="services-filters__select">
                                    <option value="">Todos los portafolios</option>
                                    @foreach ($portfolios as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="services-filters__field">
                                <select name="vigencia" class="services-filters__select">
                                    <option value="">Toda vigencia</option>
                                    <option value="expiring" @selected(($filters['vigencia'] ?? '') === 'expiring')>Por vencer (contrato, 30 días)</option>
                                    <option value="expired" @selected(($filters['vigencia'] ?? '') === 'expired')>Vencido (contrato)</option>
                                </select>
                            </div>
                            <button type="submit" class="services-filters__btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                Filtrar
                            </button>
                        </div>
                    </form>
                    <style>
                        .services-filters {
                            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                            padding: 1rem 1.25rem;
                            border-radius: 14px;
                            border: 1px solid #e2e8f0;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                            transition: box-shadow 0.2s;
                        }
                        .services-filters:hover {
                            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
                        }
                        .services-filters__inner {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 0.75rem;
                            align-items: center;
                        }
                        .services-filters__field {
                            flex: 0 1 auto;
                            min-width: 0;
                        }
                        .services-filters__field--search {
                            flex: 1 1 240px;
                            position: relative;
                        }
                        .services-filters__icon {
                            position: absolute;
                            left: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            width: 16px;
                            height: 16px;
                            color: #94a3b8;
                            pointer-events: none;
                        }
                        .services-filters__input {
                            width: 100%;
                            min-height: 40px;
                            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
                            font-size: 0.85rem;
                            border-radius: 10px;
                            border: 1.5px solid #e2e8f0;
                            background: #fff;
                            color: #1e293b;
                            transition: border-color 0.15s, box-shadow 0.15s;
                            box-sizing: border-box;
                        }
                        .services-filters__input:focus {
                            border-color: #2563eb;
                            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                            outline: none;
                        }
                        .services-filters__input::placeholder {
                            color: #94a3b8;
                        }
                        .services-filters__select {
                            min-height: 40px;
                            padding: 0.5rem 2.25rem 0.5rem 0.85rem;
                            font-size: 0.85rem;
                            border-radius: 10px;
                            border: 1.5px solid #e2e8f0;
                            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 0.65rem center;
                            background-size: 1rem;
                            color: #1e293b;
                            cursor: pointer;
                            transition: border-color 0.15s, box-shadow 0.15s;
                            appearance: none;
                            min-width: 160px;
                        }
                        .services-filters__select:focus {
                            border-color: #2563eb;
                            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                            outline: none;
                        }
                        .services-filters__select:hover {
                            border-color: #94a3b8;
                        }
                        .services-filters__btn {
                            min-height: 40px;
                            padding: 0.5rem 1.1rem;
                            font-size: 0.82rem;
                            font-weight: 600;
                            border-radius: 10px;
                            border: 1.5px solid var(--color-primary, #20214f);
                            background: var(--color-primary, #20214f);
                            color: #fff;
                            cursor: pointer;
                            transition: all 0.15s;
                            display: inline-flex;
                            align-items: center;
                            gap: 0.45rem;
                            white-space: nowrap;
                        }
                        .services-filters__btn:hover {
                            background: #18194a;
                            border-color: #18194a;
                            box-shadow: 0 2px 8px rgba(32, 33, 79, 0.25);
                        }
                        @media (max-width: 640px) {
                            .services-filters__inner {
                                flex-direction: column;
                                align-items: stretch;
                            }
                            .services-filters__field--search {
                                flex: 1 1 auto;
                            }
                            .services-filters__select {
                                width: 100%;
                                min-width: 0;
                            }
                            .services-filters__btn {
                                justify-content: center;
                            }
                        }
                    </style>

                    <div class="data-table-wrap">
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

                    <!-- DataTables maneja paginacion y selector de filas -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
