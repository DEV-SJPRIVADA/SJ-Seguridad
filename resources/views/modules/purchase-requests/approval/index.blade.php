<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])

        {{-- Subpestañas de Autorizaciones compras (misma franja que las vistas de Pedidos). --}}
        <div class="module-strip module-subnav">
            <div class="app-container">
                <div class="module-strip__inner module-subnav__inner">
                    <p class="text-caption module-subnav__label">Autorizaciones compras</p>
                    <nav class="module-tabs" aria-label="Autorizaciones compras">
                        @foreach ($approvalTabs as $tabKey => $tab)
                            <a
                                href="{{ $tab['url'] }}"
                                class="module-tab {{ $vista === $tabKey ? 'module-tab--active' : '' }}"
                                @if ($vista === $tabKey) aria-current="page" @endif
                            >
                                {{ $tab['label'] }}
                                @if (($tab['count'] ?? 0) > 0)
                                    ({{ $tab['count'] }})
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $currentEstado = $filters['estado'] ?? \App\Models\PurchaseRequest::ESTADO_PENDIENTE;
        $estadoMetaLabels = [
            \App\Models\PurchaseRequest::ESTADO_PENDIENTE => 'pendientes por autorizar',
            'todos' => 'en historial',
            \App\Models\PurchaseRequest::ESTADO_APROBADO => 'aprobadas',
            \App\Models\PurchaseRequest::ESTADO_RECHAZADO => 'rechazadas',
        ];
        // Opciones del filtro "Vista" en las subpestañas de órdenes (aprobación y anulación).
        $orderEstadoOptions = [
            \App\Models\PurchaseOrder::APROBACION_PENDIENTE => 'Pendientes por decidir',
            'todos' => 'Historial completo',
            \App\Models\PurchaseOrder::APROBACION_APROBADA => 'Aprobadas',
            \App\Models\PurchaseOrder::APROBACION_RECHAZADA => 'Rechazadas',
        ];
        $orderMetaLabels = [
            \App\Models\PurchaseOrder::APROBACION_PENDIENTE => 'pendientes por decidir',
            'todos' => 'en historial',
            \App\Models\PurchaseOrder::APROBACION_APROBADA => 'aprobadas',
            \App\Models\PurchaseOrder::APROBACION_RECHAZADA => 'rechazadas',
        ];
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            {{-- Subpestaña Órdenes de Compra: OC que Compras envió a este aprobador; pendientes por defecto, historial por filtro. --}}
            @if ($vista === 'ordenes')
                <div class="panel">
                    <div class="panel__header panel__header--compact">
                        <h3 class="panel-title">Órdenes de compra</h3>
                        <p class="panel-text panel-text--compact">Compras solicitó su aprobación para estas órdenes. Si rechaza, la orden queda anulada. Por defecto se muestran solo las pendientes; cambie la vista para consultar el historial.</p>
                    </div>
                    <div class="panel__body req-manage-shell">
                        @include('modules.purchase-requests.approval.partials.status-filter', [
                            'options' => $orderEstadoOptions,
                            'count' => $orderApprovals->count(),
                            'singular' => 'orden',
                            'plural' => 'órdenes',
                            'metaLabels' => $orderMetaLabels,
                        ])

                        <div class="data-table-wrap req-manage-shell__table">
                            <table class="supply-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>OC</th>
                                        <th>Origen</th>
                                        <th>Proveedor</th>
                                        <th>Total</th>
                                        <th>Solicitada por</th>
                                        <th>Fecha solicitud</th>
                                        <th>Decisión</th>
                                        <th>Fecha resolución</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orderApprovals as $order)
                                        @php
                                            $pillClass = match ($order->aprobacion_estado) {
                                                \App\Models\PurchaseOrder::APROBACION_APROBADA => 'status-pill--success',
                                                \App\Models\PurchaseOrder::APROBACION_RECHAZADA => 'status-pill--danger',
                                                default => 'status-pill--warning',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $order->numeroOc }}</td>
                                            <td>{{ $order->sourceFolio() }}</td>
                                            <td>{{ $order->provider_nombre }}</td>
                                            <td class="text-right">{{ number_format((float) $order->total, 2) }}</td>
                                            <td>{{ $order->aprobacionSolicitadaPor?->name ?? '—' }}</td>
                                            <td class="text-center"><x-date-table :value="$order->aprobacion_solicitada_at" datetime /></td>
                                            <td class="text-center"><span class="status-pill {{ $pillClass }}">{{ $order->aprobacionLabel() }}</span></td>
                                            <td class="text-center">
                                                @if ($order->aprobacion_resuelta_at)
                                                    <x-date-table :value="$order->aprobacion_resuelta_at" datetime />
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="table-actions">
                                                <a
                                                    href="{{ route('purchase-requests.approval.order-approval.show', ['module' => $module, 'purchase_order' => $order->getKey()]) }}"
                                                    class="btn btn--{{ $order->tieneAprobacionPendiente() ? 'primary' : 'secondary' }} btn--sm"
                                                >{{ $order->tieneAprobacionPendiente() ? 'Revisar orden' : 'Ver detalle' }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-muted">
                                                @if ($currentEstado === \App\Models\PurchaseOrder::APROBACION_PENDIENTE)
                                                    No hay órdenes de compra pendientes de aprobación.
                                                @else
                                                    No hay órdenes de compra con la vista seleccionada.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            {{-- Subpestaña Anulaciones de órdenes: OC que Compras pidió anular a este director; pendientes por defecto, historial por filtro. --}}
            @elseif ($vista === 'anulaciones')
                <div class="panel">
                    <div class="panel__header panel__header--compact">
                        <h3 class="panel-title">Anulaciones de órdenes</h3>
                        <p class="panel-text panel-text--compact">Compras pidió anular estas órdenes de compra y lo eligió a usted para decidir. Por defecto se muestran solo las pendientes; cambie la vista para consultar el historial.</p>
                    </div>
                    <div class="panel__body req-manage-shell">
                        @include('modules.purchase-requests.approval.partials.status-filter', [
                            'options' => $orderEstadoOptions,
                            'count' => $cancellations->count(),
                            'singular' => 'anulación',
                            'plural' => 'anulaciones',
                            'metaLabels' => $orderMetaLabels,
                        ])

                        <div class="data-table-wrap req-manage-shell__table">
                            <table class="supply-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>OC</th>
                                        <th>Origen</th>
                                        <th>Proveedor</th>
                                        <th>Total</th>
                                        <th>Solicitada por</th>
                                        <th>Fecha solicitud</th>
                                        <th>Decisión</th>
                                        <th>Fecha resolución</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cancellations as $order)
                                        @php
                                            $pillClass = match (true) {
                                                $order->tieneAnulacionPendiente() => 'status-pill--warning',
                                                $order->anulacion_resultado === \App\Models\PurchaseOrder::APROBACION_APROBADA => 'status-pill--danger',
                                                default => 'status-pill--muted',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $order->numeroOc }}</td>
                                            <td>{{ $order->sourceFolio() }}</td>
                                            <td>{{ $order->provider_nombre }}</td>
                                            <td class="text-right">{{ number_format((float) $order->total, 2) }}</td>
                                            <td>{{ $order->anulacionSolicitadaPor?->name ?? '—' }}</td>
                                            <td class="text-center"><x-date-table :value="$order->anulacion_solicitada_at" datetime /></td>
                                            <td class="text-center"><span class="status-pill {{ $pillClass }}">{{ $order->anulacionResultadoLabel() }}</span></td>
                                            <td class="text-center">
                                                @if ($order->anulacion_resuelta_at)
                                                    <x-date-table :value="$order->anulacion_resuelta_at" datetime />
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="table-actions">
                                                <a
                                                    href="{{ route('purchase-requests.approval.order-cancellation.show', ['module' => $module, 'purchase_order' => $order->getKey()]) }}"
                                                    class="btn btn--{{ $order->tieneAnulacionPendiente() ? 'primary' : 'secondary' }} btn--sm"
                                                >{{ $order->tieneAnulacionPendiente() ? 'Revisar anulación' : 'Ver detalle' }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-muted">
                                                @if ($currentEstado === \App\Models\PurchaseOrder::APROBACION_PENDIENTE)
                                                    No hay anulaciones de órdenes pendientes.
                                                @else
                                                    No hay anulaciones con la vista seleccionada.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else

            {{-- Subpestaña Solicitudes de Compra: solicitudes asignadas a este aprobador. --}}
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Solicitudes de compra</h3>
                    <p class="panel-text panel-text--compact">Solicitudes asignadas a usted. Por defecto se muestran solo las pendientes; cambie la vista para consultar el historial.</p>
                </div>

                <div class="panel__body req-manage-shell">
                    @include('modules.purchase-requests.approval.partials.status-filter', [
                        'options' => [
                            \App\Models\PurchaseRequest::ESTADO_PENDIENTE => 'Pendientes por autorizar',
                            'todos' => 'Historial completo',
                            \App\Models\PurchaseRequest::ESTADO_APROBADO => 'Aprobadas',
                            \App\Models\PurchaseRequest::ESTADO_RECHAZADO => 'Rechazadas',
                        ],
                        'count' => $purchaseRequests->count(),
                        'singular' => 'solicitud',
                        'plural' => 'solicitudes',
                        'metaLabels' => $estadoMetaLabels,
                    ])

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
                            style="width:100%"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Área</th>
                                    <th>N.º cotización</th>
                                    <th>Marca</th>
                                    <th>Comentarios</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Fecha resolución</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td>{{ $purchaseRequest->folioVisible() }}</td>
                                        <td><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
                                        <td>{{ $purchaseRequest->user?->name ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->areaLabel() ?? '—' }}</td>
                                        @include('modules.purchase-requests.partials.list-extra-cells', ['purchaseRequest' => $purchaseRequest])
                                        <td>{{ $purchaseRequest->items->count() }} productos</td>
                                        <td>
                                            <x-pedido-etapa :etapa="$purchaseRequest->etapa()" />
                                            @if ($purchaseRequest->urgente)
                                                <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($purchaseRequest->fecha_aprobacion)
                                                <x-date-table :value="$purchaseRequest->fecha_aprobacion" />
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'from' => 'approval']) }}" class="btn btn--{{ $purchaseRequest->estado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE ? 'primary' : 'secondary' }} btn--sm">
                                                {{ $purchaseRequest->estado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE ? 'Autorizar' : 'Ver detalle' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-muted">
                                            @if ($currentEstado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE)
                                                No hay solicitudes pendientes de autorización.
                                            @else
                                                No hay solicitudes con la vista seleccionada.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @push('styles')
        <style>
            .req-manage-filters--approval {
                padding: 0.45rem 0.75rem;
                margin-bottom: 0.75rem;
            }

            .req-manage-filters__toolbar--approval {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 2rem;
            }

            .req-manage-filters__field--approval {
                display: inline-flex;
                flex-direction: row;
                align-items: center;
                gap: 0.45rem;
                flex: 0 0 auto;
                min-width: 0;
                width: auto;
            }

            .req-manage-filters__label--inline {
                margin: 0;
                white-space: nowrap;
                line-height: 1;
            }

            /* Filtro "Vista" (searchable-select): ancho compacto y control a la altura del chrome. */
            .req-manage-filters__select--inline {
                width: auto;
                min-width: 12.5rem;
                max-width: 100%;
            }

            .req-manage-filters__select--inline .searchable-select__trigger {
                min-height: var(--control-height-chrome, 34px);
                height: var(--control-height-chrome, 34px);
                font-size: 0.8125rem;
            }

            .req-manage-filters__meta--approval {
                margin: 0;
                flex: 0 0 auto;
                text-align: right;
                font-size: 0.8125rem;
                color: #64748b;
                line-height: 1.2;
                white-space: nowrap;
            }

            @media (max-width: 640px) {
                .req-manage-filters__toolbar--approval {
                    justify-content: flex-start;
                    gap: 1rem;
                }

                .req-manage-filters__field--approval {
                    width: 100%;
                }

                .req-manage-filters__select--inline {
                    flex: 1 1 auto;
                    min-width: 0;
                }

                .req-manage-filters__meta--approval {
                    white-space: normal;
                    text-align: left;
                }
            }
        </style>
    @endpush
</x-app-layout>
