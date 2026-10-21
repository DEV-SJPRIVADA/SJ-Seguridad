<x-guest-layout>
    {{-- Anulación de OC desde el enlace firmado del correo (sin login). --}}
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Anulación de orden de compra</h1>

                @if ($result)
                    <div class="alert alert--success">
                        La anulación de la orden N.º {{ $order->numeroOc }} quedó <strong>{{ $result }}</strong>. Compras recibirá un correo con la decisión.
                    </div>
                @elseif ($alreadyResolved)
                    <div class="alert alert--info">
                        Esta solicitud ya no está pendiente. Estado actual de la orden: <strong>{{ $order->estadoLabel() }}</strong>.
                    </div>
                @else
                    <p class="req-approval-letter__lead">
                        Compras pidió <strong>anular</strong> esta orden y lo eligió a usted para aprobarlo. Si aprueba, la orden queda Anulada y su cantidad vuelve al saldo del pedido.
                    </p>
                @endif

                @include('modules.purchase-requests.partials.order-cancellation-summary', ['order' => $order])

                @if ($decideUrl)
                    @include('modules.purchase-requests.partials.order-cancellation-decision', ['decideUrl' => $decideUrl])
                @endif

                <p class="req-approval-letter__hint">
                    Este enlace es personal y tiene vigencia limitada. También puede gestionarla en Solicitudes de compra → Autorizaciones compras → Anulaciones de órdenes.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
