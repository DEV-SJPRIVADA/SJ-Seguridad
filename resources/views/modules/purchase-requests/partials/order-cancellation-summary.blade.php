{{-- Resumen de la OC y de la solicitud de anulación (plataforma y enlace del correo). --}}
<div class="req-approval-letter__panel">
    <p><strong>Orden de compra:</strong> N.º {{ $order->numeroOc }} · {{ $order->estadoLabel() }}</p>
    <p><strong>Origen:</strong> {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}</p>
    <p><strong>Proveedor:</strong> {{ $order->provider_nombre }} ({{ $order->provider_identificacion }})</p>
    <p><strong>Fecha de la orden:</strong> {{ $order->fechaImpresa() }}</p>
    <p><strong>Total:</strong> {{ number_format((float) $order->total, 2) }}</p>
    <p><strong>Solicitada por:</strong> {{ $order->anulacionSolicitadaPor?->name ?? '—' }}</p>
    <p><strong>Director que aprueba:</strong> {{ $order->anulacionAprobador?->name ?? '—' }}</p>
    @if ($order->anulacion_motivo)
        <p><strong>Motivo de la anulación:</strong> {{ $order->anulacion_motivo }}</p>
    @endif
    @if ($order->anulacion_respuesta)
        <p><strong>Comentario del director:</strong> {{ $order->anulacion_respuesta }}</p>
    @endif
</div>

{{-- Líneas de la OC: contenedor con scroll horizontal para que no desborde la tarjeta angosta del correo. --}}
<div class="req-approval-letter__table-wrap">
    <table class="req-approval-letter__table">
        <thead>
            <tr>
                <th class="text-center">Cantidad</th>
                <th>Descripción</th>
                <th class="text-right">Valor total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="text-center">{{ $item->cantidad }}</td>
                    <td class="req-approval-letter__table-desc">{{ $item->descripcion ?? '—' }}</td>
                    <td class="text-right req-approval-letter__table-num">{{ number_format((float) $item->valor_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
