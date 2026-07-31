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
                            <h3 class="panel-title">Mis solicitudes de compra</h3>
                            <p class="panel-text">Historial de solicitudes registradas por tu usuario en el area seleccionada.</p>
                        </div>
                        <a href="{{ route('purchase-requests.create', ['module' => $module]) }}" class="btn btn--primary">
                            Nueva solicitud
                        </a>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td class="text-center">{{ $purchaseRequest->folio() }}</td>
                                        <td class="text-center"><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
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
                                            @if ($purchaseRequest->urgente)
                                                <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--secondary btn--sm">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No tienes solicitudes de compra registradas.</td>
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
