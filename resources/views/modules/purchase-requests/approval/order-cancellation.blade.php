<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Revisión de la anulación de una OC por el director elegido (plataforma). --}}
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a pendientes"
                        aria-label="Volver a pendientes"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>

                <h1 class="req-approval-letter__title">Anulación de orden de compra</h1>

                @if ($order->tieneAnulacionPendiente())
                    <p class="req-approval-letter__lead">
                        Compras pidió <strong>anular</strong> esta orden. Si aprueba, la orden queda Anulada y su cantidad vuelve al saldo del pedido.
                    </p>
                @else
                    <div class="alert alert--info">
                        Esta solicitud ya fue gestionada. Estado actual de la orden: <strong>{{ $order->estadoLabel() }}</strong>.
                    </div>
                @endif

                @include('modules.purchase-requests.partials.order-cancellation-summary', ['order' => $order])

                @if ($order->tieneAnulacionPendiente())
                    @include('modules.purchase-requests.partials.order-cancellation-decision', [
                        'decideUrl' => $decideUrl,
                        'method' => 'PATCH',
                    ])
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
