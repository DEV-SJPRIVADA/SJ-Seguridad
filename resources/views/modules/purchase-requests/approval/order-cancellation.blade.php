<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $pending = $order->tieneAnulacionPendiente();
        $estadoPill = match ($order->estado) {
            \App\Models\PurchaseOrder::ESTADO_ANULADA => 'status-pill--danger',
            \App\Models\PurchaseOrder::ESTADO_ANULACION_PENDIENTE => 'status-pill--warning',
            default => 'status-pill--info',
        };
    @endphp

    {{-- Revisión de la anulación de una OC por el director elegido (plataforma). --}}
    <div class="page-section purchase-requests-page purchase-requests-page--detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a Anulaciones de órdenes"
                        aria-label="Volver a Anulaciones de órdenes"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>

                <h2 class="page-title">Anulación de orden de compra</h2>
                <p class="page-subtitle">
                    OC N.º <strong>{{ $order->numeroOc }}</strong>
                    · {{ $order->provider_nombre }}
                    @if ($order->fechaImpresa() !== '')
                        · {{ $order->fechaImpresa() }}
                    @endif
                </p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill {{ $estadoPill }}">{{ $order->estadoLabel() }}</span>
                    <span class="status-pill status-pill--muted">{{ $order->anulacionResultadoLabel() }}</span>
                    <span class="status-pill status-pill--info">Total: {{ number_format((float) $order->total, 2) }}</span>
                </div>
            </div>

            @if ($pending)
                <div class="alert alert--warning pur-review-page__notice" role="status">
                    <p><strong>Compras pidió anular esta orden.</strong> Revise el motivo y las líneas antes de decidir.
                        Si aprueba, la orden queda anulada y su cantidad vuelve al saldo del pedido.</p>
                </div>
            @else
                <div class="alert alert--info pur-review-page__notice" role="status">
                    <p>Esta solicitud ya fue gestionada. Decisión: <strong>{{ $order->anulacionResultadoLabel() }}</strong>
                        · Orden: <strong>{{ $order->estadoLabel() }}</strong>.
                        Puede consultar los datos sin modificar la decisión.</p>
                </div>
            @endif

            <div class="pur-review-page__layout">
                <div class="pur-review-page__main">
                    @include('modules.purchase-requests.partials.order-cancellation-facts', ['order' => $order])
                    @include('modules.purchase-requests.partials.order-items', ['order' => $order])
                </div>

                <aside class="pur-review-page__aside">
                    @if ($pending)
                        @include('modules.purchase-requests.partials.order-cancellation-decision', [
                            'decideUrl' => $decideUrl,
                            'method' => 'PATCH',
                        ])
                    @else
                        @include('modules.purchase-requests.partials.order-cancellation-outcome', ['order' => $order])
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
