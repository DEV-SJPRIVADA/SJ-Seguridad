<x-app-layout>

    @php
        $backUrl = route('purchase-requests.processing.index', ['module' => $module]);
        $areaLabel = config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key);
    @endphp

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
                        title="Volver a Pedidos"
                        aria-label="Volver a Pedidos"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                    <div class="pur-req-detail__toolbar-actions">
                        <a
                            href="{{ route('supplies.show', ['module' => $supplyRequest->area_key, 'supply_request' => $supplyRequest->id]) }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Ver detalle"
                            aria-label="Ver detalle"
                        >
                            <x-lucide-eye width="18" height="18" aria-hidden="true" />
                        </a>
                        @if ($supplyRequest->puedeEmitirOrden())
                            <a
                                href="{{ route('purchase-requests.orders.supply.create', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Emitir orden de compra"
                                aria-label="Emitir orden de compra"
                            >
                                <x-lucide-file-plus width="18" height="18" aria-hidden="true" />
                            </a>
                        @endif
                        @if ($supplyRequest->isExportableForCompras())
                            <a
                                href="{{ route('purchase-requests.processing.supply.pdf', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar PDF"
                                aria-label="Descargar PDF"
                                target="_blank"
                                rel="noopener"
                            >
                                <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                            </a>
                            <x-export-excel
                                route="{{ route('purchase-requests.processing.supply.excel', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar Excel"
                                aria-label="Exportar Excel"
                                target="_blank"
                                rel="noopener"
                            />
                        @endif
                    </div>
                </div>

                <h2 class="page-title">Procesar suministro {{ $supplyRequest->folioVisible() }}</h2>
                <p class="page-subtitle">El precio se captura al emitir la orden de compra. El estado pasa a Con orden al emitir una orden, o a Cerrado al cubrir el saldo o cerrarlo con motivo.</p>
                <div class="pur-req-detail__pills">
                    <x-pedido-etapa :etapa="$supplyRequest->etapa()" />
                </div>
            </div>

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <div class="pur-req-form__meta">
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Solicitante</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->user?->name ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Área</span>
                            <span class="pur-req-form__meta-value">{{ $areaLabel }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha solicitud</span>
                            <span class="pur-req-form__meta-value">
                                <x-date-table :value="$supplyRequest->created_at" />
                            </span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Sede / utilización</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->site_utilization ?? $supplyRequest->site?->utilization ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Productos</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->items->count() }}</span>
                        </div>
                    </div>

                    {{-- Líneas autorizadas en solo consulta. El precio se pide al emitir la OC, no aquí. --}}
                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step">1</span>
                            <div>
                                <h3 class="pur-req-form__section-title">Productos del suministro</h3>
                                <p class="pur-req-form__section-desc">Cantidades autorizadas por Calidad. El valor unidad y el IVA se capturan al emitir la orden de compra.</p>
                            </div>
                        </header>

                        <div class="data-table-wrap pur-req-form__table-wrap">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Cantidad autorizada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplyRequest->items as $item)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="pur-req-form__item-desc">
                                                {{ $item->displayName() }}
                                                @if ($item->is_not_in_catalog)
                                                    <span class="status-pill status-pill--warning">Fuera de catálogo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->approved_quantity ?? $item->requested_quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    @include('modules.purchase-requests.processing.partials.return-to-requester', [
                        'canReturn' => $supplyRequest->puedeDevolverAlSolicitante(),
                        'returnUrl' => route('purchase-requests.processing.supply.return', ['module' => $module, 'supply_request' => $supplyRequest->id]),
                        'returnHint' => 'Úsalo si el pedido está mal diligenciado. Al reenviarlo, Calidad debe volver a aprobarlo.',
                    ])

                    @include('modules.purchase-requests.processing.partials.close-balance', [
                        'canClose' => $supplyRequest->puedeCerrarSaldo(),
                        'closeUrl' => route('purchase-requests.processing.supply.close', ['module' => $module, 'supply_request' => $supplyRequest->id]),
                    ])
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Contexto rápido del suministro.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Folio: {{ $supplyRequest->folioVisible() }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $supplyRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Área: {{ $areaLabel }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $supplyRequest->items->count() }}</li>
                                <li class="pur-req-form-guide__item">Revisor: {{ $supplyRequest->qualityReviewer?->name ?? '—' }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Guía del pedido</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">El precio se captura al emitir la orden: valor unidad e IVA.</li>
                                <li class="pur-req-form-guide__item">Si la línea ya tenía costo, la orden lo trae como valor unidad.</li>
                                <li class="pur-req-form-guide__item">Cerrar saldo exige un motivo y deja el pedido en Cerrado.</li>
                                <li class="pur-req-form-guide__item">PDF y Excel FO-AD-44 siguen disponibles desde el detalle.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
