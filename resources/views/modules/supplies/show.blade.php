<x-app-layout>
    <x-slot name="header">
        @if ($fromComprasBandeja)
            @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
        @else
            @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
        @endif
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Solicitud de suministro {{ $supplyRequest->folio() }}</h3>
                            <p class="panel-text">
                                Estado:
                                @php
                                    $estadoPill = match ($supplyRequest->status) {
                                        'aprobada_calidad', 'completada' => 'status-pill--success',
                                        'rechazada_calidad' => 'status-pill--danger',
                                        'en_compras' => 'status-pill--info',
                                        default => 'status-pill--info',
                                    };
                                @endphp
                                <span class="status-pill {{ $estadoPill }}">{{ $supplyRequest->statusLabel() }}</span>
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            @if ($canExportFoAd44)
                                <a href="{{ route('supplies.export.pdf', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}" class="btn btn--secondary btn--sm">
                                    Descargar PDF
                                </a>
                                <x-export-excel
                                    route="{{ route('supplies.export.excel', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}"
                                    label="Exportar Excel"
                                    class="btn btn--secondary btn--sm"
                                />
                            @endif
                            @if ($fromComprasBandeja)
                                <a href="{{ route('purchase-requests.processing.index', ['module' => $purchaseModule]) }}" class="btn btn--secondary btn--sm">
                                    Volver a bandeja
                                </a>
                            @else
                                <a href="{{ route('supplies.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                                    Volver al listado
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="purchase-request-meta-grid bottom-spaced">
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de solicitud</p>
                            <p class="panel-title">{{ $supplyRequest->created_at->format('d/m/Y') }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitante</p>
                            <p class="panel-title">{{ $supplyRequest->user?->name ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Area</p>
                            <p class="panel-title">{{ config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key) }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Sede / utilizacion</p>
                            <p class="panel-title">{{ $supplyRequest->site_utilization ?? $supplyRequest->site?->utilization ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Ubicacion</p>
                            <p class="panel-title">{{ $supplyRequest->site_city ?? $supplyRequest->site?->city ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Revisor calidad</p>
                            <p class="panel-title">{{ $supplyRequest->qualityReviewer?->name ?? '—' }}</p>
                        </article>
                        @if ($supplyRequest->estadoComprasLabel())
                            <article class="card card--muted">
                                <p class="text-caption">Estado compras</p>
                                <p class="panel-title">{{ $supplyRequest->estadoComprasLabel() }}</p>
                            </article>
                        @endif
                    </div>

                    @if ($supplyRequest->quality_observations)
                        <div class="card card--muted block-spaced">
                            <p class="text-caption">Observaciones de calidad</p>
                            <p>{{ $supplyRequest->quality_observations }}</p>
                        </div>
                    @endif

                    @if ($supplyRequest->observations)
                        <div class="block-spaced">
                            <h4 class="form-label">Notas del solicitante</h4>
                            <p class="text-muted">{{ $supplyRequest->observations }}</p>
                        </div>
                    @endif

                    <div class="block-spaced">
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
                                        <td style="font-weight: 600; color: var(--color-primary);">
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

                    @if ($supplyRequest->purchasingManager || $supplyRequest->total_cost)
                        <div class="block-spaced-lg">
                            <h4 class="form-label">Procesamiento de compras</h4>
                            @if ($supplyRequest->total_cost !== null)
                                <p class="text-muted"><strong>Costo total:</strong> ${{ number_format((float) $supplyRequest->total_cost, 2) }}</p>
                            @endif
                            @if ($supplyRequest->purchasingManager)
                                <p class="text-small text-muted" style="margin-top: 0.5rem;">
                                    Procesado por: {{ $supplyRequest->purchasingManager->name }}
                                    @if ($supplyRequest->updated_at && $supplyRequest->status === 'completada')
                                        el {{ $supplyRequest->updated_at->format('d/m/Y H:i') }}
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
        </style>
    @endpush
</x-app-layout>
