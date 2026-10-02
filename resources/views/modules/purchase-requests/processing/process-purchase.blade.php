<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

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
                        title="Volver a bandeja de compras"
                        aria-label="Volver a bandeja de compras"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                    <div class="pur-req-detail__toolbar-actions">
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

                <h2 class="page-title">Procesar solicitud {{ $purchaseRequest->folio() }}</h2>
                <p class="page-subtitle">Actualiza el estado operativo de Compras y deja comentarios visibles para el solicitante.</p>
                <div class="pur-req-detail__pills">
                    @if ($estadoCompras)
                        <span class="status-pill status-pill--compras-{{ $estadoCompras }}">
                            Compras: {{ $purchaseRequest->estadoComprasLabel() }}
                        </span>
                    @endif
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
                            <span class="pur-req-form__meta-label">Area</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->areaLabel() ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha solicitud</span>
                            <span class="pur-req-form__meta-value">
                                <x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" />
                            </span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Productos</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->items->count() }}</span>
                        </div>
                    </div>

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step">1</span>
                            <div>
                                <h3 class="pur-req-form__section-title">Productos de la solicitud</h3>
                                <p class="pur-req-form__section-desc">Lineas FO-AD-44 (solo consulta).</p>
                            </div>
                        </header>

                        <div class="data-table-wrap pur-req-form__table-wrap">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cantidad</th>
                                        <th>Descripcion</th>
                                        <th>Referencia</th>
                                        <th>Utilizacion</th>
                                        <th>Ubicacion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseRequest->items as $item)
                                        <tr>
                                            <td class="text-center">{{ $item->orden ?? $loop->iteration }}</td>
                                            <td class="text-center">{{ $item->cantidad }}</td>
                                            <td class="pur-req-form__item-desc">{{ $item->descripcion }}</td>
                                            <td>{{ $item->referencia }}</td>
                                            <td>{{ $item->utilizacion }}</td>
                                            <td>{{ $item->ubicacion }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <form
                        action="{{ route('purchase-requests.processing.purchase.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                        method="POST"
                        class="form-stack"
                        id="purchase-process-form"
                    >
                        @csrf
                        @method('PATCH')

                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">2</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Estado y comentarios</h3>
                                    <p class="pur-req-form__section-desc">Define el avance en bandeja y deja observaciones para el solicitante.</p>
                                </div>
                            </header>

                            <div class="form-grid form-grid--two">
                                <div class="form-field">
                                    <label class="form-label" for="estado_compras">Estado de compras</label>
                                    <x-searchable-select
                                        id="estado_compras"
                                        name="estado_compras"
                                        :options="\App\Models\PurchaseRequest::estadosComprasLabels()"
                                        :value="old('estado_compras', $purchaseRequest->estado_compras)"
                                        placeholder="Seleccione estado…"
                                        searchPlaceholder="Buscar estado…"
                                        :required="true"
                                        :allowClear="false"
                                    />
                                    <x-input-error :messages="$errors->get('estado_compras')" />
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label" for="comentarios_compras">Comentarios</label>
                                <textarea
                                    name="comentarios_compras"
                                    id="comentarios_compras"
                                    class="form-textarea"
                                    rows="4"
                                    placeholder="Observaciones del area de compras…"
                                >{{ old('comentarios_compras', $purchaseRequest->comentarios_compras) }}</textarea>
                                <x-input-error :messages="$errors->get('comentarios_compras')" />
                            </div>
                        </section>

                        <div class="pur-req-form-actions">
                            <p class="pur-req-form-actions__note">
                                Al guardar, el solicitante puede recibir notificacion segun el estado elegido.
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
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Guardar procesamiento"
                                    aria-label="Guardar procesamiento"
                                >
                                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Contexto rapido de la solicitud.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Folio: {{ $purchaseRequest->folio() }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $purchaseRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Director: {{ $purchaseRequest->aprobador?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $purchaseRequest->items->count() }}</li>
                                @if ($estadoCompras)
                                    <li class="pur-req-form-guide__item">Estado actual: {{ $purchaseRequest->estadoComprasLabel() }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Guia de estados</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item"><strong>Pendiente</strong>: recien llegada a bandeja.</li>
                                <li class="pur-req-form-guide__item"><strong>En curso</strong>: Compras ya gestiona la compra.</li>
                                <li class="pur-req-form-guide__item"><strong>Completado</strong>: tramite cerrado.</li>
                                <li class="pur-req-form-guide__item"><strong>Rechazado</strong>: no procede (explica en comentarios).</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
