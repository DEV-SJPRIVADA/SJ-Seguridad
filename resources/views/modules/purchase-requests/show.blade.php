<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Solicitud de compra {{ $purchaseRequest->folio() }}</h3>
                            <p class="panel-text">
                                Estado:
                                @php
                                    $estadoPill = match ($purchaseRequest->estado) {
                                        'aprobado' => 'status-pill--success',
                                        'rechazado' => 'status-pill--danger',
                                        default => 'status-pill--info',
                                    };
                                    $estadoLabel = match ($purchaseRequest->estado) {
                                        'aprobado' => 'Aprobado',
                                        'rechazado' => 'Rechazado',
                                        default => 'Pendiente',
                                    };
                                @endphp
                                <span class="status-pill {{ $estadoPill }}">{{ $estadoLabel }}</span>
                                @if ($purchaseRequest->urgente)
                                    <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                @endif
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ route('purchase-requests.export.pdf', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--secondary btn--sm">
                                Descargar PDF
                            </a>
                            <x-export-excel
                                route="{{ route('purchase-requests.export.excel', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                label="Exportar Excel"
                                class="btn btn--secondary btn--sm"
                            />
                            @can('resubmit', $purchaseRequest)
                                <a
                                    href="{{ route('purchase-requests.edit', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                    class="btn btn--secondary btn--sm purchase-request-resubmit-btn"
                                    title="Reabrir y editar"
                                    aria-label="Reabrir y editar"
                                >
                                    <x-ri-issues-reopen-fill :size="18" />
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="purchase-request-meta-grid bottom-spaced">
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de solicitud</p>
                            <p class="panel-title">{{ optional($purchaseRequest->fecha_solicitud)->format('d/m/Y') ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitante</p>
                            <p class="panel-title">{{ $purchaseRequest->user?->name ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Area</p>
                            <p class="panel-title">{{ $purchaseRequest->areaLabel() ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitud para</p>
                            <p class="panel-title">{{ $purchaseRequest->solicitud_para }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Director aprobador</p>
                            <p class="panel-title">{{ $purchaseRequest->aprobador?->name ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de aprobacion</p>
                            <p class="panel-title">{{ optional($purchaseRequest->fecha_aprobacion)->format('d/m/Y') ?? '—' }}</p>
                        </article>
                        @if ($purchaseRequest->estado_compras)
                            <article class="card card--muted">
                                <p class="text-caption">Estado compras</p>
                                <p class="panel-title">{{ $purchaseRequest->estadoComprasLabel() }}</p>
                            </article>
                        @endif
                    </div>

                    @if ($purchaseRequest->solicitud_para === 'Cliente')
                        <div class="card card--info block-spaced">
                            <p class="text-caption">Datos del cliente</p>
                            <p><strong>Razon social:</strong> {{ $purchaseRequest->razon_social ?? '—' }}</p>
                            <p><strong>Proyecto nuevo:</strong> {{ $purchaseRequest->proyecto_nuevo ? 'Si' : 'No' }}</p>
                            <p><strong>Asume el cliente:</strong> {{ $purchaseRequest->asume_cliente ? 'Si' : 'No' }}</p>
                        </div>
                    @endif

                    @if ($purchaseRequest->comentarios_director)
                        <div class="card card--muted block-spaced">
                            <p class="text-caption">Comentarios del director</p>
                            <p>{{ $purchaseRequest->comentarios_director }}</p>
                        </div>
                    @endif

                    @if (filled($purchaseRequest->descripcion))
                        <div class="block-spaced">
                            <h4 class="form-label">Descripcion general</h4>
                            <p class="purchase-request-text-block">{{ $purchaseRequest->descripcion }}</p>
                        </div>
                    @endif

                    @if (filled($purchaseRequest->justificacion))
                        <div class="block-spaced">
                            <h4 class="form-label">Justificacion</h4>
                            <p class="purchase-request-text-block">{{ $purchaseRequest->justificacion }}</p>
                        </div>
                    @endif

                    <div class="block-spaced">
                        <table class="supply-table purchase-request-items-table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-foto">Foto</th>
                                    <th class="col-qty">Cantidad</th>
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
                                        <td class="text-center purchase-item-photo-cell">
                                            @if ($item->fotoUrl())
                                                <a href="{{ $item->fotoUrl() }}" target="_blank" rel="noopener" class="purchase-item-photo-link" title="Ver foto ampliada">
                                                    <img
                                                        src="{{ $item->fotoUrl() }}"
                                                        alt="Foto del producto"
                                                        class="purchase-item-photo-thumb"
                                                        loading="lazy"
                                                    >
                                                </a>
                                            @else
                                                <span class="text-muted text-small">Sin foto</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->cantidad }}</td>
                                        <td style="font-weight: 600; color: var(--color-primary);">{{ $item->descripcion }}</td>
                                        <td>{{ $item->referencia }}</td>
                                        <td>{{ $item->utilizacion }}</td>
                                        <td>{{ $item->ubicacion }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('modules.purchase-requests.partials.approval-form')

                    <div class="block-spaced-lg">
                        <h4 class="form-label">Registro de correos de esta solicitud</h4>
                        @if ($purchaseRequest->mailLogs->isEmpty())
                            <p class="text-muted text-small">No hay correos registrados para esta solicitud.</p>
                        @else
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / hora</th>
                                        <th>Tipo</th>
                                        <th>Destinatario</th>
                                        <th>Estado</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseRequest->mailLogs as $mailLog)
                                        <tr>
                                            <td>{{ optional($mailLog->sent_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td>{{ $mailLog->typeLabel() }}</td>
                                            <td>{{ $mailLog->recipient_email }}</td>
                                            <td>
                                                @php
                                                    $mailStatusClass = $mailLog->status === \App\Models\PurchaseRequestMailLog::STATUS_ENVIADO
                                                        ? 'status-pill--success'
                                                        : 'status-pill--danger';
                                                @endphp
                                                <span class="status-pill {{ $mailStatusClass }}">{{ $mailLog->statusLabel() }}</span>
                                            </td>
                                            <td>{{ $mailLog->detail ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    @if ($purchaseRequest->comentarios_compras)
                        <div class="block-spaced-lg">
                            <h4 class="form-label">Comentarios de compras</h4>
                            <p class="text-muted">{{ $purchaseRequest->comentarios_compras }}</p>
                            @if ($purchaseRequest->procesadoComprasPor)
                                <p class="text-small text-muted" style="margin-top: 0.5rem;">
                                    Procesado por: {{ $purchaseRequest->procesadoComprasPor->name }}
                                    @if ($purchaseRequest->procesado_compras_at)
                                        el {{ $purchaseRequest->procesado_compras_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .purchase-request-meta-grid {
                display: grid;
                gap: 0.5rem;
                margin-bottom: 1rem;
                grid-template-columns: 1fr;
            }

            .purchase-request-meta-grid .card {
                padding: 0.55rem 0.65rem;
                margin: 0;
            }

            .purchase-request-meta-grid .text-caption {
                margin-bottom: 0.15rem;
                font-size: 0.72rem;
            }

            .purchase-request-meta-grid .panel-title {
                font-size: 0.95rem;
                line-height: 1.25;
                margin: 0;
                word-break: break-word;
            }

            @media (min-width: 768px) {
                .purchase-request-meta-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            @media (min-width: 1280px) {
                .purchase-request-meta-grid {
                    grid-template-columns: repeat(7, minmax(0, 1fr));
                }
            }

            .purchase-request-text-block {
                margin: 0;
                padding: 0.65rem 0.75rem;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                white-space: pre-wrap;
                word-break: break-word;
                line-height: 1.45;
            }

            .purchase-request-items-table .col-num {
                width: 48px;
            }

            .purchase-request-items-table .col-foto {
                width: 96px;
            }

            .purchase-request-items-table .col-qty {
                width: 80px;
            }

            .purchase-item-photo-cell {
                vertical-align: middle;
                padding: 0.5rem;
            }

            .purchase-item-photo-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 72px;
                height: 56px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                background: #fff;
                overflow: hidden;
            }

            .purchase-item-photo-thumb {
                display: block;
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
                object-fit: contain;
            }

            .purchase-request-resubmit-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding-inline: 0.45rem;
                line-height: 1;
            }
        </style>
    @endpush
</x-app-layout>
