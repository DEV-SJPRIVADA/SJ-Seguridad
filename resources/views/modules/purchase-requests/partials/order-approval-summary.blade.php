{{-- Resumen de la OC y de la solicitud de aprobación (plataforma y enlace del correo). --}}
<div class="req-approval-letter__panel">
    <p><strong>Orden de compra:</strong> N.º {{ $order->numeroOc }} · {{ $order->estadoLabel() }} · {{ $order->aprobacionLabel() }}</p>
    <p><strong>Origen:</strong> {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}</p>
    <p><strong>Proveedor:</strong> {{ $order->provider_nombre }} ({{ $order->provider_identificacion }})</p>
    <p><strong>Fecha de la orden:</strong> {{ $order->fechaImpresa() }}</p>
    <p><strong>Total:</strong> {{ number_format((float) $order->total, 2) }}</p>
    <p><strong>Solicitada por:</strong> {{ $order->aprobacionSolicitadaPor?->name ?? '—' }}</p>
    <p><strong>Aprobador:</strong> {{ $order->aprobacionAprobador?->name ?? '—' }}</p>
    @if ($order->aprobacion_nota)
        <p><strong>Nota de Compras:</strong> {{ $order->aprobacion_nota }}</p>
    @endif
    @if ($order->aprobacion_comentario)
        <p><strong>Comentario del aprobador:</strong> {{ $order->aprobacion_comentario }}</p>
    @endif
</div>

{{-- Líneas de la OC: contenedor con scroll horizontal para que no desborde la tarjeta angosta del correo. --}}
<div class="req-approval-letter__table-wrap">
    <table class="req-approval-letter__table">
        <thead>
            <tr>
                <th class="text-center">Cantidad</th>
                <th>Referencia</th>
                <th>Descripción</th>
                <th class="text-right">Valor unidad</th>
                <th class="text-right">IVA</th>
                <th class="text-right">Valor total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->cantidad, 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $item->articulo ?? '—' }}</td>
                    <td class="req-approval-letter__table-desc">{{ $item->descripcion ?? '—' }}</td>
                    <td class="text-right req-approval-letter__table-num">{{ number_format((float) $item->valor_unidad, 2) }}</td>
                    <td class="text-right req-approval-letter__table-num">{{ $item->ivaImpreso() }}</td>
                    <td class="text-right req-approval-letter__table-num">{{ number_format((float) $item->valor_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
