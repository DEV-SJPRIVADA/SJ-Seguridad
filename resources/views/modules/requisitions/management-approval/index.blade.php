<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Autorizacion gerencia — cargo nuevo</h3>
                    <p class="panel-text">Requisiciones pendientes de autorizacion. Al aprobar o rechazar, salen de esta lista.</p>
                </div>
                <div class="panel__body">
                    @if (session('status'))
                        <div class="alert alert--success block-spaced" role="status">{{ session('status') }}</div>
                    @endif

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" data-no-excel data-order='[[1, "desc"]]'>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Cargo</th>
                                    <th>Area</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requisitions as $requisition)
                                    <tr>
                                        <td>{{ $requisition->code }}</td>
                                        <td>{{ $requisition->request_date?->format('Y-m-d') }}</td>
                                        <td>{{ $requisition->requester?->name }}</td>
                                        <td>{{ $requisition->position?->name }}</td>
                                        <td>{{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}</td>
                                        <td>
                                            <span class="status-pill status-pill--req-pendiente_autorizacion_gerencia">
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('requisitions.management-approval.show', ['module' => $moduleKey, 'requisition' => $requisition]) }}" class="btn btn--primary btn--sm">Revisar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No hay requisiciones pendientes de autorizacion.</td>
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
