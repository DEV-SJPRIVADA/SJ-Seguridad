@php
    $activeCount = $services->where('is_active', true)->count();
    $totalCount = $services->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.comercial.partials.gestion-clientes-subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section comercial-clients-page comercial-clients-page--detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif

            <div class="comercial-detail__toolbar">
                <a href="{{ route('comercial.matriz.clients.index') }}" class="comercial-detail__back">
                    <x-lucide-arrow-left width="16" height="16" aria-hidden="true" />
                    Volver al listado
                </a>
                <div class="comercial-detail__toolbar-actions">
                    @if ($canManage)
                        <a href="{{ route('comercial.matriz.clients.edit', $client) }}" class="btn btn--secondary btn--sm">Editar cliente</a>
                        <a href="{{ route('comercial.matriz.services.create', ['client' => $client->id]) }}" class="btn btn--primary btn--sm">Agregar servicio</a>
                    @endif
                </div>
            </div>

            <div class="comercial-detail-layout">
                <div class="comercial-detail-layout__main">
                    <div class="comercial-form__meta">
                        <div class="comercial-form__meta-item comercial-form__meta-item--wide">
                            <span class="comercial-form__meta-label">Cliente</span>
                            <span class="comercial-form__meta-value">{{ $client->name }}</span>
                        </div>
                        <div class="comercial-form__meta-item">
                            <span class="comercial-form__meta-label">NIT</span>
                            <span class="comercial-form__meta-value">{{ $client->nit }}</span>
                        </div>
                        <div class="comercial-form__meta-item">
                            <span class="comercial-form__meta-label">Ciudad</span>
                            <span class="comercial-form__meta-value">{{ $client->city ?: 'Sin ciudad' }}</span>
                        </div>
                        <div class="comercial-form__meta-item">
                            <span class="comercial-form__meta-label">Servicios</span>
                            <span class="comercial-form__meta-value">{{ $totalCount }}</span>
                        </div>
                        <div class="comercial-form__meta-item">
                            <span class="comercial-form__meta-label">Estado operativo</span>
                            <span class="comercial-form__meta-value">
                                @if ($activeCount > 0)
                                    <span class="status-pill status-pill--success">Activo</span>
                                @else
                                    <span class="status-pill status-pill--danger">Inactivo</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <section class="comercial-form__section">
                        <header class="comercial-form__section-head">
                            <span class="comercial-form__section-step" aria-hidden="true">
                                <x-lucide-building-2 width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="comercial-form__section-title">Datos del cliente</h3>
                                <p class="comercial-form__section-desc">Informacion maestra del NIT.</p>
                            </div>
                        </header>

                        <div class="comercial-form__meta">
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Telefono</span>
                                <span class="comercial-form__meta-value">{{ $client->phone ?: '—' }}</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Direccion</span>
                                <span class="comercial-form__meta-value">{{ $client->address ?: '—' }}</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">R. Legal</span>
                                <span class="comercial-form__meta-value">{{ $client->legal_rep_name ?: '—' }}</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Doc. RL</span>
                                <span class="comercial-form__meta-value">{{ $client->legal_rep_doc ?: '—' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="comercial-form__section">
                        <header class="comercial-form__section-head">
                            <span class="comercial-form__section-step" aria-hidden="true">
                                <x-lucide-briefcase-business width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="comercial-form__section-title">Servicios / contratos</h3>
                                <p class="comercial-form__section-desc">Un cliente puede tener lineas en distintos portafolios.</p>
                            </div>
                        </header>

                        <form method="GET" class="comercial-detail__filter">
                            <div class="form-field">
                                <label class="form-label" for="client-show-portfolio">Portafolio</label>
                                <x-searchable-select
                                    id="client-show-portfolio"
                                    name="portfolio"
                                    :options="$portfolios"
                                    :value="$filters['portfolio'] ?? ''"
                                    placeholder="Todos los portafolios"
                                    searchPlaceholder="Buscar portafolio…"
                                    :allowClear="true"
                                />
                            </div>
                            <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                        </form>

                        <div class="data-table-wrap">
                            <table class="data-table js-datatable" data-dt-compact="true">
                                <thead>
                                    <tr>
                                        <th>Portafolio</th>
                                        <th>Contrato</th>
                                        <th>Tipo</th>
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
                                            <td>{{ $portfolios[$service->portfolio] ?? $service->portfolio }}</td>
                                            <td><span class="comercial-list__code">{{ $service->contract_number ?: '—' }}</span></td>
                                            <td>{{ $service->serviceType?->name ?: '—' }}</td>
                                            <td>{{ $service->advisor_name ?: '—' }}</td>
                                            <td><x-date-table :value="$service->contract_start" /></td>
                                            <td><x-date-table :value="$service->contract_end" /></td>
                                            <td>
                                                <span class="status-pill {{ $estadoPillClass }}">{{ $estadoLabel }}</span>
                                            </td>
                                            <td class="table-actions">
                                                @if ($canManage)
                                                    <div class="comercial-detail__row-actions">
                                                        <a href="{{ route('comercial.matriz.services.edit', $service) }}" class="btn btn--secondary btn--sm">Editar</a>
                                                        @if (! $service->is_active)
                                                            <form method="POST" action="{{ route('comercial.matriz.services.activate', $service) }}">
                                                                @csrf
                                                                <button type="submit" class="btn btn--primary btn--sm">Activar</button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('comercial.matriz.services.inactivate', $service) }}" onsubmit="return confirm('¿Inactivar este servicio?');">
                                                                @csrf
                                                                <button type="submit" class="btn btn--secondary btn--sm">Inactivar</button>
                                                            </form>
                                                        @endif
                                                    </div>
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
                    </section>
                </div>

                <aside class="comercial-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Datos rapidos del NIT.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="comercial-form-guide__list">
                                <li class="comercial-form-guide__item">Servicios activos: {{ $activeCount }}</li>
                                <li class="comercial-form-guide__item">Total servicios: {{ $totalCount }}</li>
                                <li class="comercial-form-guide__item">Ciudad: {{ $client->city ?: '—' }}</li>
                                <li class="comercial-form-guide__item">Telefono: {{ $client->phone ?: '—' }}</li>
                            </ul>
                        </div>
                    </div>

                    @if ($canManage)
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Acciones</h3>
                            </div>
                            <div class="panel__body comercial-detail__aside-actions">
                                <a href="{{ route('comercial.matriz.clients.edit', $client) }}" class="btn btn--secondary">Editar cliente</a>
                                <a href="{{ route('comercial.matriz.services.create', ['client' => $client->id]) }}" class="btn btn--primary">Agregar servicio</a>
                            </div>
                        </div>
                    @endif

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Recordatorio</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="comercial-form-guide__list">
                                <li class="comercial-form-guide__item">El estado del cliente depende de servicios activos.</li>
                                <li class="comercial-form-guide__item">Inactivar un servicio no cambia su portafolio.</li>
                                <li class="comercial-form-guide__item">Use Checklist documental desde el listado de clientes.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
