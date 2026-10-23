<x-guest-layout>
    {{-- Aprobación de OC desde el enlace firmado del correo (sin login). --}}
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Aprobación de orden de compra</h1>

                @if ($result)
                    <div class="alert alert--success">
                        La orden N.º {{ $order->numeroOc }} quedó <strong>{{ $result }}</strong>. Compras recibirá un correo con la decisión.
                    </div>
                @elseif ($alreadyResolved)
                    <div class="alert alert--info">
                        Esta solicitud ya no está pendiente. Aprobación: <strong>{{ $order->aprobacionLabel() }}</strong> · Orden: <strong>{{ $order->estadoLabel() }}</strong>.
                    </div>
                @else
                    <p class="req-approval-letter__lead">
                        Compras solicita su <strong>aprobación</strong> para esta orden de compra.
                    </p>
                @endif

                @include('modules.purchase-requests.partials.order-approval-summary', ['order' => $order])

                @if ($decideUrl)
                    @include('modules.purchase-requests.partials.order-approval-decision', ['decideUrl' => $decideUrl])
                @endif

                <p class="req-approval-letter__hint">
                    Este enlace es personal y tiene vigencia limitada. También puede gestionarla en Solicitudes de compra → Autorizaciones compras → Órdenes de Compra.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
