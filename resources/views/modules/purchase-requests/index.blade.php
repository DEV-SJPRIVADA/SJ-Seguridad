<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="alert">{{ session('status') }}</div>
            @endif

            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif

            <div class="panel purchase-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="pur-req-list__header-row">
                        <div>
                            <h3 class="panel-title">Listado</h3>
                            <p class="panel-text panel-text--compact">
                                {{ $purchaseRequests->count() }}
                                {{ $purchaseRequests->count() === 1 ? 'solicitud' : 'solicitudes' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
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
                                    <th>Estado compras</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td class="text-center">
                                            <span class="pur-req-list__folio">{{ $purchaseRequest->folio() }}</span>
                                        </td>
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
                                            <div class="pur-req-list__pills">
                                                <span class="status-pill {{ $estadoPill }}">{{ $estadoLabel }}</span>
                                                @if ($purchaseRequest->urgente)
                                                    <span class="status-pill status-pill--warning">Urgente</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if ($purchaseRequest->estado_compras)
                                                <span class="status-pill status-pill--compras-{{ $purchaseRequest->estado_compras }}">
                                                    {{ $purchaseRequest->estadoComprasLabel() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                <a
                                                    href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'from' => 'mis_solicitudes']) }}"
                                                    class="btn btn--secondary btn--sm"
                                                >Ver detalle</a>
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
                                        <td colspan="9" class="text-muted">No tienes solicitudes de compra registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
