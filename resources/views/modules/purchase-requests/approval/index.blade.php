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
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            {{-- Subpestaña Anulaciones de órdenes: OC que Compras pidió anular y envió a este director. --}}
            @if ($vista === 'anulaciones')
                <div class="panel">
                    <div class="panel__header panel__header--compact">
                        <h3 class="panel-title">Anulaciones de órdenes por aprobar</h3>
                        <p class="panel-text panel-text--compact">Compras pidió anular estas órdenes de compra y lo eligió a usted para decidir.</p>
                    </div>
                    <div class="panel__body req-manage-shell">
                        <div class="data-table-wrap req-manage-shell__table">
                            <table class="supply-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>OC</th>
                                        <th>Origen</th>
                                        <th>Proveedor</th>
                                        <th>Total</th>
                                        <th>Solicitada por</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pendingCancellations as $order)
                                        <tr>
                                            <td class="text-center">{{ $order->numeroOc }}</td>
                                            <td>{{ $order->sourceFolio() }}</td>
                                            <td>{{ $order->provider_nombre }}</td>
                                            <td class="text-right">{{ number_format((float) $order->total, 2) }}</td>
                                            <td>{{ $order->anulacionSolicitadaPor?->name ?? '—' }}</td>
                                            <td class="text-center"><x-date-table :value="$order->anulacion_solicitada_at" datetime /></td>
                                            <td class="table-actions">
                                                <a
                                                    href="{{ route('purchase-requests.approval.order-cancellation.show', ['module' => $module, 'purchase_order' => $order->getKey()]) }}"
                                                    class="btn btn--primary btn--sm"
                                                >Revisar anulación</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-muted">No hay anulaciones de órdenes pendientes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else

            {{-- Subpestaña Pendientes de autorización: solicitudes de compra asignadas a este director. --}}
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Pendientes de autorización</h3>
                    <p class="panel-text panel-text--compact">Solicitudes asignadas a ti. Por defecto se muestran solo las pendientes; cambia la vista para consultar el historial.</p>
                </div>

                <div class="panel__body req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <div class="req-manage-filters req-manage-filters--approval">
                            <form
                                method="GET"
                                action="{{ route('purchase-requests.approval.index', ['module' => $module]) }}"
                                class="req-manage-filters__toolbar req-manage-filters__toolbar--approval"
                            >
                                <div class="req-manage-filters__field req-manage-filters__field--approval">
                                    <label class="req-manage-filters__label req-manage-filters__label--inline" for="approval-filter-estado">Vista</label>
                                    <select
                                        id="approval-filter-estado"
                                        name="estado"
                                        class="form-select req-manage-filters__select--inline"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_PENDIENTE }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE)>
                                            Pendientes por autorizar
                                        </option>
                                        <option value="todos" @selected($currentEstado === 'todos')>Historial completo</option>
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_APROBADO }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_APROBADO)>
                                            Aprobadas
                                        </option>
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_RECHAZADO }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_RECHAZADO)>
                                            Rechazadas
                                        </option>
                                    </select>
                                </div>

                                <p class="req-manage-filters__meta req-manage-filters__meta--approval">
                                    <strong>{{ number_format($purchaseRequests->count()) }}</strong>
                                    {{ $purchaseRequests->count() === 1 ? 'solicitud' : 'solicitudes' }}
                                    {{ $estadoMetaLabels[$currentEstado] ?? '' }}
                                </p>
                            </form>
                        </div>
                    </div>

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
                                        <td colspan="8" class="text-muted">
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

            .req-manage-filters__select--inline {
                width: auto;
                min-width: 11.5rem;
                max-width: 100%;
                min-height: 34px;
                height: 34px;
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
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
