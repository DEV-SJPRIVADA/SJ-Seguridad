<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Pendientes de autorizacion</h3>
                    <p class="panel-text">Solicitudes de compra asignadas a ti como director aprobador.</p>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Area</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td>{{ $purchaseRequest->folio() }}</td>
                                        <td><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
                                        <td>{{ $purchaseRequest->user?->name ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->areaLabel() ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->items->count() }} productos</td>
                                        <td>
                                            <span class="status-pill status-pill--info">Pendiente</span>
                                            @if ($purchaseRequest->urgente)
                                                <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--primary btn--sm">
                                                Autorizar
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No hay solicitudes pendientes de autorizacion.</td>
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
