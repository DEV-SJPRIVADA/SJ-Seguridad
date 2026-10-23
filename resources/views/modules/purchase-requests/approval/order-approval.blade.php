<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Revisión de la aprobación de una OC por el aprobador elegido (plataforma). --}}
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a Órdenes de Compra"
                        aria-label="Volver a Órdenes de Compra"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>

                <h1 class="req-approval-letter__title">Aprobación de orden de compra</h1>

                @if ($order->tieneAprobacionPendiente())
                    <p class="req-approval-letter__lead">
                        Compras solicita su <strong>aprobación</strong> para esta orden.
                    </p>
                @else
                    <div class="alert alert--info">
                        Esta solicitud ya fue gestionada. Aprobación: <strong>{{ $order->aprobacionLabel() }}</strong> · Orden: <strong>{{ $order->estadoLabel() }}</strong>.
                    </div>
                @endif

                @include('modules.purchase-requests.partials.order-approval-summary', ['order' => $order])

                @if ($order->tieneAprobacionPendiente())
                    @include('modules.purchase-requests.partials.order-approval-decision', [
                        'decideUrl' => $decideUrl,
                        'method' => 'PATCH',
                    ])
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
