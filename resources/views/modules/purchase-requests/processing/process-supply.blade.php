<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $backUrl = route('purchase-requests.processing.index', ['module' => $module]);
        $areaLabel = config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key);
        $estadoCompras = $supplyRequest->estado_compras;
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
                        title="Volver a bandeja de compras"
                        aria-label="Volver a bandeja de compras"
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
                        @if ($supplyRequest->isExportableForCompras())
                            <a
                                href="{{ route('purchase-requests.processing.supply.pdf', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar PDF"
                                aria-label="Descargar PDF"
                            >
                                <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                            </a>
                            <x-export-excel
                                route="{{ route('purchase-requests.processing.supply.excel', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar Excel"
                                aria-label="Exportar Excel"
                            />
                        @endif
                    </div>
                </div>

                <h2 class="page-title">Procesar suministro {{ $supplyRequest->folio() }}</h2>
                <p class="page-subtitle">Registra el costo unitario de cada línea autorizada y completa el procesamiento en bandeja.</p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill status-pill--info">{{ $supplyRequest->statusLabel() }}</span>
                    @if ($estadoCompras)
                        <span class="status-pill status-pill--compras-{{ $estadoCompras }}">
                            Compras: {{ $supplyRequest->estadoComprasLabel() }}
                        </span>
                    @endif
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

                    <form
                        action="{{ route('purchase-requests.processing.supply.update', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                        method="POST"
                        class="form-stack"
                        id="supply-process-form"
                    >
                        @csrf
                        @method('PATCH')

                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">1</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Costeo de productos</h3>
                                    <p class="pur-req-form__section-desc">Ingresa el costo unitario de cada línea con cantidad autorizada.</p>
                                </div>
                            </header>

                            <div class="data-table-wrap pur-req-form__table-wrap">
                                <table class="supply-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Producto</th>
                                            <th>Cant. autorizada</th>
                                            <th style="width: 180px;">Costo unitario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($supplyRequest->items as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td class="pur-req-form__item-desc">
                                                    {{ $item->displayName() }}
                                                    @if ($item->is_not_in_catalog)
                                                        <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Fuera de catálogo</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->approved_quantity ?? $item->requested_quantity }}</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        name="items[{{ $item->id }}][unit_cost]"
                                                        class="supply-input"
                                                        step="0.01"
                                                        min="0"
                                                        value="{{ old('items.'.$item->id.'.unit_cost', $item->unit_cost) }}"
                                                        required
                                                        aria-label="Costo unitario de {{ $item->displayName() }}"
                                                    >
                                                    <x-input-error :messages="$errors->get('items.'.$item->id.'.unit_cost')" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <div class="pur-req-form-actions">
                            <p class="pur-req-form-actions__note">
                                Al completar, la solicitud queda cerrada en bandeja con el costo total calculado.
                            </p>
                            <div class="pur-req-form-actions__group">
                                <a
                                    href="{{ $backUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar y volver"
                                    aria-label="Cancelar y volver"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    name="action"
                                    value="complete"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Completar procesamiento"
                                    aria-label="Completar procesamiento"
                                >
                                    <x-lucide-check width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Contexto rapido del suministro.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Folio: {{ $supplyRequest->folio() }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $supplyRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Área: {{ $areaLabel }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $supplyRequest->items->count() }}</li>
                                <li class="pur-req-form-guide__item">Revisor: {{ $supplyRequest->qualityReviewer?->name ?? '—' }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Guía de costeo</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Usa la cantidad autorizada por Calidad.</li>
                                <li class="pur-req-form-guide__item">El costo unitario es obligatorio en cada línea.</li>
                                <li class="pur-req-form-guide__item">Al completar se calcula el costo total.</li>
                                <li class="pur-req-form-guide__item">PDF/Excel FO-AD-44 siguen disponibles desde el detalle.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
