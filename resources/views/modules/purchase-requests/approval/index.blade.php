<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $currentEstado = $filters['estado'] ?? \App\Models\PurchaseRequest::ESTADO_PENDIENTE;
        $estadoMetaLabels = [
            \App\Models\PurchaseRequest::ESTADO_PENDIENTE => 'pendientes por autorizar',
            'todos' => 'en historial',
            \App\Models\PurchaseRequest::ESTADO_APROBADO => 'aprobadas',
            \App\Models\PurchaseRequest::ESTADO_RECHAZADO => 'rechazadas',
        ];
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Pendientes de autorizacion</h3>
                    <p class="panel-text panel-text--compact">Solicitudes asignadas a ti. Por defecto se muestran solo las pendientes; cambia la vista para consultar el historial.</p>
                </div>

                <div class="panel__body req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <div class="req-manage-filters req-manage-filters--approval">
                            <form
                                method="GET"
                                action="{{ route('purchase-requests.approval.index', ['module' => $module]) }}"
                                class="req-manage-filters__toolbar req-manage-filters__toolbar--approval"
                            >
                                <div class="req-manage-filters__field req-manage-filters__field--approval">
                                    <label class="req-manage-filters__label req-manage-filters__label--inline" for="approval-filter-estado">Vista</label>
                                    <select
                                        id="approval-filter-estado"
                                        name="estado"
                                        class="form-select req-manage-filters__select--inline"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_PENDIENTE }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE)>
                                            Pendientes por autorizar
                                        </option>
                                        <option value="todos" @selected($currentEstado === 'todos')>Historial completo</option>
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_APROBADO }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_APROBADO)>
                                            Aprobadas
                                        </option>
                                        <option value="{{ \App\Models\PurchaseRequest::ESTADO_RECHAZADO }}" @selected($currentEstado === \App\Models\PurchaseRequest::ESTADO_RECHAZADO)>
                                            Rechazadas
                                        </option>
                                    </select>
                                </div>

                                <p class="req-manage-filters__meta req-manage-filters__meta--approval">
                                    <strong>{{ number_format($purchaseRequests->count()) }}</strong>
                                    {{ $purchaseRequests->count() === 1 ? 'solicitud' : 'solicitudes' }}
                                    {{ $estadoMetaLabels[$currentEstado] ?? '' }}
                                </p>
                            </form>
                        </div>
                    </div>

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
                                    <th>Area</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Fecha resolucion</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    @php
                                        $estadoPill = match ($purchaseRequest->estado) {
                                            \App\Models\PurchaseRequest::ESTADO_APROBADO => 'status-pill--success',
                                            \App\Models\PurchaseRequest::ESTADO_RECHAZADO => 'status-pill--danger',
                                            default => 'status-pill--info',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $purchaseRequest->folio() }}</td>
                                        <td><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
                                        <td>{{ $purchaseRequest->user?->name ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->areaLabel() ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->items->count() }} productos</td>
                                        <td>
                                            <span class="status-pill {{ $estadoPill }}">{{ $purchaseRequest->estadoLabel() }}</span>
                                            @if ($purchaseRequest->urgente)
                                                <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($purchaseRequest->fecha_aprobacion)
                                                <x-date-table :value="$purchaseRequest->fecha_aprobacion" />
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--{{ $purchaseRequest->estado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE ? 'primary' : 'secondary' }} btn--sm">
                                                {{ $purchaseRequest->estado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE ? 'Autorizar' : 'Ver detalle' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">
                                            @if ($currentEstado === \App\Models\PurchaseRequest::ESTADO_PENDIENTE)
                                                No hay solicitudes pendientes de autorizacion.
                                            @else
                                                No hay solicitudes con la vista seleccionada.
                                            @endif
                                        </td>
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
            .req-manage-filters--approval {
                padding: 0.45rem 0.75rem;
                margin-bottom: 0.75rem;
            }

            .req-manage-filters__toolbar--approval {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 2rem;
            }

            .req-manage-filters__field--approval {
                display: inline-flex;
                flex-direction: row;
                align-items: center;
                gap: 0.45rem;
                flex: 0 0 auto;
                min-width: 0;
                width: auto;
            }

            .req-manage-filters__label--inline {
                margin: 0;
                white-space: nowrap;
                line-height: 1;
            }

            .req-manage-filters__select--inline {
                width: auto;
                min-width: 11.5rem;
                max-width: 100%;
                min-height: 34px;
                height: 34px;
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
                font-size: 0.8125rem;
            }

            .req-manage-filters__meta--approval {
                margin: 0;
                flex: 0 0 auto;
                text-align: right;
                font-size: 0.8125rem;
                color: #64748b;
                line-height: 1.2;
                white-space: nowrap;
            }

            @media (max-width: 640px) {
                .req-manage-filters__toolbar--approval {
                    justify-content: flex-start;
                    gap: 1rem;
                }

                .req-manage-filters__field--approval {
                    width: 100%;
                }

                .req-manage-filters__select--inline {
                    flex: 1 1 auto;
                    min-width: 0;
                }

                .req-manage-filters__meta--approval {
                    white-space: normal;
                    text-align: left;
                }
            }
        </style>
    @endpush
</x-app-layout>
