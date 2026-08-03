<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Bandeja de compras</h3>
                    <p class="panel-text">Cola unificada de solicitudes de compra aprobadas y suministros listos para procesamiento.</p>
                </div>

                <div class="panel__body">
                    <form method="GET" action="{{ route('purchase-requests.processing.index', ['module' => $module]) }}" class="approved-filters bottom-spaced">
                        <select name="tipo" class="form-select approved-filters__control" title="Tipo">
                            <option value="">Todos los tipos</option>
                            <option value="purchase" @selected(($filters['tipo'] ?? '') === 'purchase')>Solicitud compra</option>
                            <option value="supply" @selected(($filters['tipo'] ?? '') === 'supply')>Suministro</option>
                        </select>

                        <select name="estado_compras" class="form-select approved-filters__control" title="Estado">
                            <option value="">Todos los estados</option>
                            @foreach (\App\Models\PurchaseRequest::estadosComprasLabels() as $estadoKey => $estadoLabel)
                                <option value="{{ $estadoKey }}" @selected(($filters['estado_compras'] ?? '') === $estadoKey)>{{ $estadoLabel }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                        <a href="{{ route('purchase-requests.processing.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">Limpiar</a>
                    </form>

                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Solicitante</th>
                                <th>Area</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($queueItems as $queueItem)
                                <tr>
                                    <td>{{ $queueItem['tipo_label'] }}</td>
                                    <td>{{ $queueItem['folio'] }}</td>
                                    <td><x-date-table :value="$queueItem['fecha']" datetime /></td>
                                    <td>{{ $queueItem['solicitante'] ?? '—' }}</td>
                                    <td>{{ $queueItem['area'] ?? '—' }}</td>
                                    <td>
                                        @php
                                            $estadoPill = match ($queueItem['estado'] ?? '') {
                                                'completado' => 'status-pill--success',
                                                'rechazado' => 'status-pill--danger',
                                                'en_curso' => 'status-pill--warning',
                                                default => 'status-pill--info',
                                            };
                                        @endphp
                                        <span class="status-pill {{ $estadoPill }}">{{ $queueItem['estado_label'] ?? '—' }}</span>
                                    </td>
                                    <td class="table-actions">
                                        @if ($queueItem['tipo'] === 'purchase')
                                            <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                Ver detalle
                                            </a>
                                            <a href="{{ route('purchase-requests.processing.purchase', ['module' => $module, 'purchase_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                Procesar
                                            </a>
                                        @else
                                            <a href="{{ route('supplies.show', ['module' => $queueItem['model']->area_key, 'supply_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                Ver detalle
                                            </a>
                                            <a href="{{ route('purchase-requests.processing.supply', ['module' => $module, 'supply_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                Procesar
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">No hay elementos en la bandeja con los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .approved-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .approved-filters__control {
            min-height: 38px;
            min-width: 0;
            padding: 0.4rem 0.65rem;
            font-size: 0.875rem;
        }

        .approved-filters select.approved-filters__control {
            flex: 0 1 180px;
            max-width: 220px;
        }
    </style>
</x-app-layout>
