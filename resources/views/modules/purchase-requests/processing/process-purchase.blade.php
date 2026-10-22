<x-app-layout>

    @php
        $backUrl = route('purchase-requests.processing.index', ['module' => $module]);
        $estadoCompras = $purchaseRequest->estado_compras;
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
                        @if ($purchaseRequest->puedeEmitirOrden())
                            <a
                                href="{{ route('purchase-requests.orders.purchase.create', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Emitir orden de compra"
                                aria-label="Emitir orden de compra"
                            >
                                <x-lucide-file-plus width="18" height="18" aria-hidden="true" />
                            </a>
                        @endif
                        <a
                            href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'from' => 'processing']) }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Ver detalle"
                            aria-label="Ver detalle"
                        >
                            <x-lucide-eye width="18" height="18" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <h2 class="page-title">Procesar solicitud {{ $purchaseRequest->folioVisible() }}</h2>
                <p class="page-subtitle">El estado lo calcula el sistema según las órdenes emitidas. Puedes cerrar el saldo con un motivo.</p>
                <div class="pur-req-detail__pills">
                    <x-pedido-etapa :etapa="$purchaseRequest->etapa()" />
                    @if ($purchaseRequest->urgente)
                        <span class="status-pill status-pill--warning">Urgente</span>
                    @endif
                </div>
            </div>

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <div class="pur-req-form__meta">
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Solicitante</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->user?->name ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Área</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->areaLabel() ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha solicitud</span>
                            <span class="pur-req-form__meta-value">
                                <x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" />
                            </span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Número de cotización</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->numero_cotizacion ?: '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Productos</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->items->count() }}</span>
                        </div>
                    </div>

                    {{-- Comentarios que el solicitante escribió en Datos generales. --}}
                    @if (filled($purchaseRequest->comentarios_solicitante))
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-message-square-text width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Comentarios del solicitante</h3>
                                </div>
                            </header>
                            <p class="pur-req-chat__body">{{ $purchaseRequest->comentarios_solicitante }}</p>
                        </section>
                    @endif

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step">1</span>
                            <div>
                                <h3 class="pur-req-form__section-title">Productos de la solicitud</h3>
                                <p class="pur-req-form__section-desc">Líneas FO-AD-44 (solo consulta).</p>
                            </div>
                        </header>

                        <div class="data-table-wrap pur-req-form__table-wrap">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cantidad</th>
                                        <th>Descripción</th>
                                        <th>Referencia</th>
                                        <th>Marca</th>
                                        <th>Utilización</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseRequest->items as $item)
                                        <tr>
                                            <td class="text-center">{{ $item->orden ?? $loop->iteration }}</td>
                                            <td class="text-center">{{ $item->cantidad }}</td>
                                            <td class="pur-req-form__item-desc">{{ $item->descripcion }}</td>
                                            <td>{{ $item->referencia }}</td>
                                            <td>{{ $item->marca ?: '—' }}</td>
                                            <td>{{ $item->utilizacion }}</td>
                                            <td>{{ $item->ubicacion }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- El estado ya no se elige a mano: lo escribe la máquina al emitir OC o al cerrar saldo. --}}
                    @if ($purchaseRequest->comentarios_compras)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">2</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Comentarios de compras</h3>
                                    <p class="pur-req-form__section-desc">Nota guardada en un pedido anterior. Los motivos nuevos van al hilo.</p>
                                </div>
                            </header>
                            <p class="pur-req-detail__block-body">{{ $purchaseRequest->comentarios_compras }}</p>
                        </section>
                    @endif

                    @include('modules.purchase-requests.processing.partials.return-to-requester', [
                        'canReturn' => $purchaseRequest->puedeDevolverAlSolicitante(),
                        'returnUrl' => route('purchase-requests.processing.purchase.return', ['module' => $module, 'purchase_request' => $purchaseRequest->id]),
                        'returnHint' => 'Úsalo si la solicitud está mal diligenciada. Al reenviarla, el director debe volver a autorizarla.',
                    ])

                    @include('modules.purchase-requests.processing.partials.close-balance', [
                        'canClose' => $purchaseRequest->puedeCerrarSaldo(),
                        'closeUrl' => route('purchase-requests.processing.purchase.close', ['module' => $module, 'purchase_request' => $purchaseRequest->id]),
                    ])
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Contexto rápido de la solicitud.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Folio: {{ $purchaseRequest->folioVisible() }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $purchaseRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Director: {{ $purchaseRequest->aprobador?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $purchaseRequest->items->count() }}</li>
                                @if ($estadoCompras)
                                    <li class="pur-req-form-guide__item">Estado actual: {{ $purchaseRequest->etiquetaFlujoCompras() }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Guía de estados</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item"><strong>Por comprar</strong>: aprobada y todavía sin cantidades en una orden.</li>
                                <li class="pur-req-form-guide__item"><strong>Con orden</strong>: hay una orden y queda saldo.</li>
                                <li class="pur-req-form-guide__item"><strong>Cerrado</strong>: saldo en cero o cierre con motivo. No admite otra orden.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
