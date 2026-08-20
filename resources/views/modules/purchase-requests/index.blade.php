<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <div class="purchase-requests-index-header">
                        <div>
                            <h3 class="panel-title">Mis solicitudes de compra</h3>
                            <p class="panel-text panel-text--compact">Historial de solicitudes registradas por tu usuario en el area seleccionada.</p>
                        </div>
                        <a href="{{ route('purchase-requests.create', ['module' => $module]) }}" class="btn btn--primary btn--sm">
                            Nueva solicitud
                        </a>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    @if (session('status'))
                        <div class="alert alert--success bottom-spaced" role="alert">{{ session('status') }}</div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert--warning bottom-spaced" role="alert">{{ session('warning') }}</div>
                    @endif

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
                            style="width:100%"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Director aprobador</th>
                                    <th>Fecha de aprobacion</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td class="text-center">{{ $purchaseRequest->folio() }}</td>
                                        <td class="text-center"><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
                                        <td>{{ $purchaseRequest->user?->name ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->aprobador?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($purchaseRequest->fecha_aprobacion)
                                                <x-date-table :value="$purchaseRequest->fecha_aprobacion" />
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $purchaseRequest->items->count() }}</td>
                                        <td class="text-center">
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

                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--secondary btn--sm">
                                                    Ver detalle
                                                </a>
                                                @can('resubmit', $purchaseRequest)
                                                    <a
                                                        href="{{ route('purchase-requests.edit', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                                        class="btn btn--secondary btn--sm purchase-request-resubmit-btn"
                                                        title="Reabrir y editar"
                                                        aria-label="Reabrir y editar"
                                                    >
                                                        <x-ri-issues-reopen-fill width="18" height="18" aria-hidden="true" />
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">No tienes solicitudes de compra registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .purchase-requests-index-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .purchase-request-actions-col {
                width: 1%;
                min-width: 11.5rem;
                white-space: nowrap;
            }

            .purchase-request-row-actions {
                display: inline-flex;
                gap: 0.35rem;
                justify-content: center;
                align-items: center;
                flex-wrap: nowrap;
                white-space: nowrap;
            }

            .purchase-request-row-actions .btn {
                flex-shrink: 0;
                white-space: nowrap;
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
