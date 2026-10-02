@php
    $estadoPill = match ($supplyRequest->status) {
        'aprobada_calidad', 'completada' => 'status-pill--success',
        'rechazada_calidad' => 'status-pill--danger',
        'en_compras' => 'status-pill--info',
        default => 'status-pill--info',
    };
    $areaLabel = config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key);
    $backUrl = $fromComprasBandeja
        ? route('purchase-requests.processing.index', ['module' => $purchaseModule])
        : route('supplies.index', ['module' => $module]);
    $backLabel = $fromComprasBandeja ? 'Volver a bandeja' : 'Volver al listado';
@endphp

<x-app-layout>
    <x-slot name="header">
        @if ($fromComprasBandeja)
            @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
        @else
            @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
        @endif
    </x-slot>

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
                        title="{{ $backLabel }}"
                        aria-label="{{ $backLabel }}"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                    <div class="pur-req-detail__toolbar-actions">
                        @if ($canExportFoAd44)
                            <a
                                href="{{ route('supplies.export.pdf', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar PDF"
                                aria-label="Descargar PDF"
                            >
                                <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                            </a>
                            <x-export-excel
                                route="{{ route('supplies.export.excel', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar Excel"
                                aria-label="Exportar Excel"
                            />
                        @endif
                        @if ($fromComprasBandeja && in_array($supplyRequest->status, ['aprobada_calidad', 'en_compras'], true))
                            <a
                                href="{{ route('purchase-requests.processing.supply', ['module' => $purchaseModule, 'supply_request' => $supplyRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Procesar suministro"
                                aria-label="Procesar suministro"
                            >
                                <x-lucide-clipboard-check width="18" height="18" aria-hidden="true" />
                            </a>
                        @endif
                    </div>
                </div>

                <h2 class="page-title">Solicitud de suministro {{ $supplyRequest->folio() }}</h2>
                <p class="page-subtitle">Detalle FO-AD-44 · seguimiento de calidad y compras.</p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill {{ $estadoPill }}">{{ $supplyRequest->statusLabel() }}</span>
                    @if ($supplyRequest->estadoComprasLabel())
                        <span class="status-pill status-pill--compras-{{ $supplyRequest->estado_compras }}">
                            Compras: {{ $supplyRequest->estadoComprasLabel() }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <div class="pur-req-form__meta">
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha de solicitud</span>
                            <span class="pur-req-form__meta-value">
                                <x-date-table :value="$supplyRequest->created_at" />
                            </span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Solicitante</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->user?->name ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Area</span>
                            <span class="pur-req-form__meta-value">{{ $areaLabel }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Sede / utilizacion</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->site_utilization ?? $supplyRequest->site?->utilization ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Ubicacion</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->site_city ?? $supplyRequest->site?->city ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Revisor calidad</span>
                            <span class="pur-req-form__meta-value">{{ $supplyRequest->qualityReviewer?->name ?? '—' }}</span>
                        </div>
                        @if ($supplyRequest->estadoComprasLabel())
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Estado compras</span>
                                <span class="pur-req-form__meta-value">{{ $supplyRequest->estadoComprasLabel() }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($supplyRequest->quality_observations)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-message-square-text width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Observaciones de calidad</h3>
                                    <p class="pur-req-form__section-desc">Notas registradas en la revision de Calidad.</p>
                                </div>
                            </header>
                            <p class="pur-req-detail__block-body">{{ $supplyRequest->quality_observations }}</p>
                        </section>
                    @endif

                    @if ($supplyRequest->observations)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-sticky-note width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Notas del solicitante</h3>
                                    <p class="pur-req-form__section-desc">Observaciones enviadas con la solicitud.</p>
                                </div>
                            </header>
                            <p class="pur-req-detail__block-body">{{ $supplyRequest->observations }}</p>
                        </section>
                    @endif

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step" aria-hidden="true">
                                <x-lucide-package width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="pur-req-form__section-title">Productos solicitados</h3>
                                <p class="pur-req-form__section-desc">{{ $supplyRequest->items->count() }} linea(s) en esta solicitud.</p>
                            </div>
                        </header>

                        <div class="data-table-wrap">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Foto</th>
                                        <th>Cantidad</th>
                                        <th>Descripcion</th>
                                        <th>Referencia</th>
                                        <th>Inventario reportado</th>
                                        <th>Cant. autorizada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplyRequest->items as $item)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center">
                                                <span class="text-muted text-small">Sin foto</span>
                                            </td>
                                            <td class="text-center">{{ $item->requested_quantity }}</td>
                                            <td class="pur-req-detail__item-name">
                                                {{ $item->displayName() }}
                                                @if ($item->is_not_in_catalog)
                                                    <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Fuera de catalogo</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->referenceLabel() }}</td>
                                            <td class="text-center">
                                                @if ($item->is_not_in_catalog)
                                                    <span class="text-muted">N/A</span>
                                                @else
                                                    {{ $item->current_inventory }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($supplyRequest->status === 'pendiente_calidad')
                                                    <span class="text-muted">—</span>
                                                @else
                                                    {{ $item->approved_quantity ?? '—' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    @if ($supplyRequest->purchasingManager || $supplyRequest->total_cost)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-wallet width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Procesamiento de compras</h3>
                                    <p class="pur-req-form__section-desc">Costeo y cierre registrado en bandeja.</p>
                                </div>
                            </header>
                            <div class="pur-req-form__meta">
                                @if ($supplyRequest->total_cost !== null)
                                    <div class="pur-req-form__meta-item">
                                        <span class="pur-req-form__meta-label">Costo total</span>
                                        <span class="pur-req-form__meta-value">${{ number_format((float) $supplyRequest->total_cost, 2) }}</span>
                                    </div>
                                @endif
                                @if ($supplyRequest->purchasingManager)
                                    <div class="pur-req-form__meta-item">
                                        <span class="pur-req-form__meta-label">Procesado por</span>
                                        <span class="pur-req-form__meta-value">{{ $supplyRequest->purchasingManager->name }}</span>
                                    </div>
                                    @if ($supplyRequest->updated_at && $supplyRequest->status === 'completada')
                                        <div class="pur-req-form__meta-item">
                                            <span class="pur-req-form__meta-label">Fecha procesamiento</span>
                                            <span class="pur-req-form__meta-value">
                                                <x-date-table :value="$supplyRequest->updated_at" datetime />
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Contexto rapido de la solicitud.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Folio: {{ $supplyRequest->folio() }}</li>
                                <li class="pur-req-form-guide__item">Estado: {{ $supplyRequest->statusLabel() }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $supplyRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Area: {{ $areaLabel }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $supplyRequest->items->count() }}</li>
                                @if ($supplyRequest->estadoComprasLabel())
                                    <li class="pur-req-form-guide__item">Compras: {{ $supplyRequest->estadoComprasLabel() }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Flujo</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item"><strong>Pendiente calidad</strong>: espera revision.</li>
                                <li class="pur-req-form-guide__item"><strong>Aprobada</strong>: lista para bandeja Compras.</li>
                                <li class="pur-req-form-guide__item"><strong>En compras</strong>: costeo en curso.</li>
                                <li class="pur-req-form-guide__item"><strong>Completada</strong>: tramite cerrado.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
