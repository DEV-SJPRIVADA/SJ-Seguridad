<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $subtotal = $order->items->sum(fn ($item) => ((float) $item->cantidad) * ((float) $item->valor_unidad));
        $ivaTotal = $order->items->sum(fn ($item) => (float) $item->iva);
    @endphp

    <div class="page-section purchase-requests-page purchase-requests-page--detail purchase-orders-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver al procesamiento"
                        aria-label="Volver al procesamiento"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                    <div class="pur-req-detail__toolbar-actions">
                        @if (! empty($evaluationUrl))
                            <a
                                href="{{ $evaluationUrl }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Evaluar entrega"
                                aria-label="Evaluar entrega"
                            >
                                <x-lucide-clipboard-check width="18" height="18" aria-hidden="true" />
                            </a>
                        @endif
                        @if ($sourceUrl)
                            <a
                                href="{{ $sourceUrl }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Ver origen"
                                aria-label="Ver origen"
                            >
                                <x-lucide-eye width="18" height="18" aria-hidden="true" />
                            </a>
                        @endif
                        <a
                            href="{{ $pdfUrl }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Descargar PDF"
                            aria-label="Descargar PDF"
                            target="_blank"
                            rel="noopener"
                        >
                            <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                        </a>
                        <x-export-excel
                            id="purchase-order-excel"
                            route="{{ $excelUrl }}"
                            label=""
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Exportar Excel"
                            aria-label="Exportar Excel FO-AD-07"
                            target="_blank"
                            rel="noopener"
                        />
                    </div>
                </div>

                <h2 class="page-title">Orden de compra N.º {{ $order->numeroOc }}</h2>
                <p class="page-subtitle">FO-AD-07 · {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }} · {{ $order->fecha }}</p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill status-pill--{{ $order->estado === 'emitida' ? 'success' : 'info' }}">{{ $order->estadoLabel() }}</span>
                    @if ($order->asume_cliente !== null)
                        <span class="status-pill status-pill--info">Asume cliente: {{ $order->asume_cliente }}</span>
                    @endif
                </div>
            </div>

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step">1</span>
                            <div>
                                <h3 class="pur-req-form__section-title">Proveedor</h3>
                                <p class="pur-req-form__section-desc">Datos congelados al emitir la orden.</p>
                            </div>
                        </header>

                        <div class="pur-req-form__meta">
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Identificación</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_identificacion }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Nombre</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_nombre }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Contacto</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_contacto ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Teléfono</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_telefono ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Correo</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_correo ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Ciudad</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_ciudad ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Dirección</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_direccion ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Forma de pago</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_forma_pago ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Días de pago</span>
                                <span class="pur-req-form__meta-value">{{ $order->provider_dias_pago ?? '—' }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Ciudad de entrega</span>
                                <span class="pur-req-form__meta-value">{{ $order->ciudad ?? '—' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step">2</span>
                            <div>
                                <h3 class="pur-req-form__section-title">Detalle de la orden</h3>
                                <p class="pur-req-form__section-desc">Líneas FO-AD-07 con precios y totales.</p>
                            </div>
                        </header>

                        <div class="data-table-wrap pur-req-form__table-wrap">
                            <table class="supply-table purchase-order-lines">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cantidad</th>
                                        <th>Artículo</th>
                                        <th>Descripción</th>
                                        <th>Saldo origen</th>
                                        <th class="text-right">Valor unidad</th>
                                        <th class="text-right">IVA</th>
                                        <th class="text-right">Valor total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        @php
                                            $saldo = $item->source_item_id !== null ? ($sourceLines[$item->source_item_id] ?? null) : null;
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $item->orden }}</td>
                                            <td class="text-center">{{ $item->cantidad }}</td>
                                            <td>{{ $item->articulo ?? '—' }}</td>
                                            <td class="pur-req-form__item-desc">{{ $item->descripcion ?? '—' }}</td>
                                            <td class="js-line-pending">
                                                @if ($saldo)
                                                    <span class="purchase-order-lines__pending-value">
                                                        {{ number_format((float) $saldo['pendiente'], 2) }}
                                                    </span>
                                                    <span class="purchase-order-lines__pending-hint">
                                                        de {{ number_format((float) $saldo['solicitada'], 0) }} solicitadas
                                                        · {{ number_format((float) $saldo['ordenada'], 0) }} ordenadas
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format((float) $item->valor_unidad, 2) }}</td>
                                            <td class="text-right">{{ number_format((float) $item->iva, 2) }}</td>
                                            <td class="text-right">{{ number_format((float) $item->valor_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-right">Subtotal</th>
                                        <th class="text-right">{{ number_format((float) $subtotal, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="7" class="text-right">IVA</th>
                                        <th class="text-right">{{ number_format((float) $ivaTotal, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="7" class="text-right">TOTAL</th>
                                        <th class="text-right">{{ number_format((float) $order->total, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if ($order->observaciones)
                            <div class="form-field" style="margin-top: 0.85rem;">
                                <span class="form-label">Observaciones</span>
                                <p class="pur-req-form__item-desc">{{ $order->observaciones }}</p>
                            </div>
                        @endif
                    </section>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Datos de la orden emitida.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">OC: {{ $order->numeroOc }}</li>
                                <li class="pur-req-form-guide__item">Origen: {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}</li>
                                <li class="pur-req-form-guide__item">Fecha: {{ $order->fecha }}</li>
                                <li class="pur-req-form-guide__item">Líneas: {{ $order->items->count() }}</li>
                                <li class="pur-req-form-guide__item">Total: {{ number_format((float) $order->total, 2) }}</li>
                                <li class="pur-req-form-guide__item">Estado: {{ $order->estadoLabel() }}</li>
                                <li class="pur-req-form-guide__item">
                                    Recibido:
                                    {{ $order->evaluation?->fecha_recibido?->format('d/m/Y') ?? 'pendiente' }}
                                </li>
                                <li class="pur-req-form-guide__item">
                                    <a href="{{ route('purchase-requests.orders.index', ['module' => $module]) }}">
                                        Ver todas las órdenes de compra
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Documento</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">FO-AD-07 · {{ config('purchase-orders.form_period') }} · Versión {{ config('purchase-orders.form_version') }}</li>
                                <li class="pur-req-form-guide__item">El número de OC corresponde al identificador interno.</li>
                                <li class="pur-req-form-guide__item">La evaluación post-entrega no se imprime en el documento.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Otras órdenes de esta solicitud</h3>
                            <p class="panel-text">
                                @if ($siblings->isEmpty())
                                    Esta es la única orden emitida para esta solicitud.
                                @else
                                    Reparto entre proveedores: {{ $siblings->count() + 1 }} órdenes en total.
                                @endif
                            </p>
                        </div>
                        <div class="panel__body">
                            @if ($siblings->isNotEmpty())
                                <ul class="pur-req-form-guide__list">
                                    @foreach ($siblings as $sibling)
                                        <li class="pur-req-form-guide__item">
                                            <a href="{{ route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $sibling->getKey()]) }}">
                                                OC N.º {{ $sibling->numeroOc }}
                                            </a>
                                            — {{ $sibling->provider_nombre }}
                                            — {{ $sibling->fecha }}
                                            — {{ number_format((float) $sibling->total, 2) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
