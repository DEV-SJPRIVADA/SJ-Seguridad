{{-- Órdenes de compra del pedido (solicitante, director/Calidad y Compras): número, proveedor, valores, estado, recibido y PDF FO-AD-07.
     Las anuladas se muestran marcadas. $orderPdfUrl es un closure que arma la ruta del PDF según el origen. --}}
@if ($orders->isNotEmpty())
    <section class="pur-req-form__section" id="source-purchase-orders">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-file-check width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Órdenes de compra</h3>
                <p class="pur-req-form__section-desc">
                    {{ $orders->count() }} {{ $orders->count() === 1 ? 'orden emitida' : 'órdenes emitidas' }} para este pedido. Descargue el PDF FO-AD-07 de cada una.
                </p>
            </div>
        </header>

        <div class="data-table-wrap">
            <table class="supply-table">
                <thead>
                    <tr>
                        <th>OC</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">IVA</th>
                        <th class="text-right">Total</th>
                        <th>Estado</th>
                        <th>Recibido</th>
                        <th>PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $iva = $order->items->sum(fn ($line) => (float) $line->iva);
                            $pill = match ($order->estado) {
                                \App\Models\PurchaseOrder::ESTADO_EMITIDA => 'status-pill--success',
                                \App\Models\PurchaseOrder::ESTADO_ANULACION_PENDIENTE => 'status-pill--warning',
                                \App\Models\PurchaseOrder::ESTADO_ANULADA => 'status-pill--danger',
                                default => 'status-pill--muted',
                            };
                        @endphp
                        <tr @if ($order->isAnulada()) class="text-muted" @endif>
                            <td class="text-center">{{ $order->numeroOc }}</td>
                            <td class="text-center">{{ $order->fechaImpresa() }}</td>
                            <td>{{ $order->provider_nombre }}</td>
                            <td class="text-right">{{ number_format((float) $order->total - $iva, 2) }}</td>
                            <td class="text-right">{{ number_format($iva, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $order->total, 2) }}</td>
                            <td><span class="status-pill {{ $pill }}">{{ $order->estadoLabel() }}</span></td>
                            <td class="text-center">{{ $order->evaluation?->fecha_recibido?->format('d/m/Y') ?? 'Pendiente' }}</td>
                            <td class="text-center">
                                <a
                                    href="{{ $orderPdfUrl($order) }}"
                                    class="cursos-catalogo-page__icon-btn"
                                    title="Descargar PDF FO-AD-07 de la OC {{ $order->numeroOc }}"
                                    aria-label="Descargar PDF FO-AD-07 de la OC {{ $order->numeroOc }}"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <x-lucide-file-text width="16" height="16" aria-hidden="true" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Valor unitario de cada línea: el detalle completo está en el PDF. --}}
        <details class="pur-req-orders__lines">
            <summary>Ver líneas y valores unitarios</summary>
            <div class="data-table-wrap">
                <table class="supply-table">
                    <thead>
                        <tr>
                            <th>OC</th>
                            <th>Cantidad</th>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th class="text-right">Valor unidad</th>
                            <th class="text-right">IVA</th>
                            <th class="text-right">Valor total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @foreach ($order->items as $line)
                                <tr @if ($order->isAnulada()) class="text-muted" @endif>
                                    <td class="text-center">{{ $order->numeroOc }}{{ $order->isAnulada() ? ' (anulada)' : '' }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $line->cantidad, 2, '.', ''), '0'), '.') }}</td>
                                    <td>{{ $line->articulo ?? '—' }}</td>
                                    <td>{{ $line->descripcion ?? '—' }}</td>
                                    <td class="text-right">{{ number_format((float) $line->valor_unidad, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->iva, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->valor_total, 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>
@endif
