{{-- Líneas y montos de la OC (revisión del aprobador en plataforma). --}}
@php
    $subtotal = $order->items->sum(fn ($item) => ((float) $item->cantidad) * ((float) $item->valor_unidad));
    $ivaTotal = $order->items->sum(fn ($item) => (float) $item->iva);
@endphp

<section class="pur-review-panel">
    <header class="pur-review-panel__head">
        <div>
            <h3 class="pur-review-panel__title">Productos de la orden</h3>
            <p class="pur-review-panel__sub">Detalle de las líneas y montos que se autorizan.</p>
        </div>
        <span class="status-pill status-pill--info">{{ $order->items->count() }} línea(s)</span>
    </header>

    <div class="pur-ord-lines__wrap">
        <table class="pur-ord-lines">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Cantidad</th>
                    <th>Artículo</th>
                    <th>Descripción</th>
                    <th class="text-right">Valor unidad</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">Valor total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td class="text-center">{{ $item->orden }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->cantidad, 2, '.', ''), '0'), '.') }}</td>
                        <td>{{ $item->articulo ?? '—' }}</td>
                        <td class="pur-ord-lines__desc">{{ $item->descripcion ?? '—' }}</td>
                        <td class="text-right">{{ number_format((float) $item->valor_unidad, 2) }}</td>
                        <td class="text-right">{{ $item->ivaImpreso() }}</td>
                        <td class="text-right">{{ number_format((float) $item->valor_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-right">Subtotal</th>
                    <th class="text-right">{{ number_format((float) $subtotal, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="6" class="text-right">IVA</th>
                    <th class="text-right">{{ number_format((float) $ivaTotal, 2) }}</th>
                </tr>
                <tr class="pur-ord-lines__total">
                    <th colspan="6" class="text-right">Total</th>
                    <th class="text-right">{{ number_format((float) $order->total, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
