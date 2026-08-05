<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $currentEstado = $filters['estado'] ?? \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_PENDIENTE;
        $estadoMetaLabels = [
            \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_PENDIENTE => 'pendientes por autorizar',
            \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_TODOS => 'en historial',
            \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_APROBADA => 'autorizadas',
            \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_RECHAZADA => 'rechazadas',
        ];
    @endphp

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Autorizacion gerencia — cargo nuevo</h3>
                    <p class="panel-text">Por defecto se muestran solo las pendientes; cambie la vista para consultar autorizadas o rechazadas.</p>
                </div>
                <div class="panel__body">
                    @if (session('status'))
                        <div class="alert alert--success block-spaced" role="status">{{ session('status') }}</div>
                    @endif

                    <div class="req-manage-filters req-manage-filters--approval">
                        <form
                            method="GET"
                            action="{{ route('requisitions.management-approval.index', ['module' => $moduleKey]) }}"
                            class="req-manage-filters__toolbar req-manage-filters__toolbar--approval"
                        >
                            <div class="req-manage-filters__field req-manage-filters__field--approval">
                                <label class="req-manage-filters__label req-manage-filters__label--inline" for="management-filter-estado">Vista</label>
                                <select
                                    id="management-filter-estado"
                                    name="estado"
                                    class="form-select req-manage-filters__select--inline"
                                    onchange="this.form.submit()"
                                >
                                    <option value="{{ \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_PENDIENTE }}" @selected($currentEstado === \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_PENDIENTE)>
                                        Pendientes por autorizar
                                    </option>
                                    <option value="{{ \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_TODOS }}" @selected($currentEstado === \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_TODOS)>
                                        Historial completo
                                    </option>
                                    <option value="{{ \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_APROBADA }}" @selected($currentEstado === \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_APROBADA)>
                                        Autorizadas
                                    </option>
                                    <option value="{{ \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_RECHAZADA }}" @selected($currentEstado === \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_RECHAZADA)>
                                        Rechazadas
                                    </option>
                                </select>
                            </div>

                            <p class="req-manage-filters__meta req-manage-filters__meta--approval">
                                <strong>{{ number_format($requisitions->count()) }}</strong>
                                {{ $requisitions->count() === 1 ? 'requisicion' : 'requisiciones' }}
                                {{ $estadoMetaLabels[$currentEstado] ?? '' }}
                            </p>
                        </form>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" data-order='[[1, "desc"]]'>
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
                                    @php
                                        $estadoPill = match ($requisition->status) {
                                            \App\Models\PersonalRequisition::STATUS_SOLICITADA => 'status-pill--success',
                                            \App\Models\PersonalRequisition::STATUS_CANCELADA => 'status-pill--danger',
                                            \App\Models\PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA => 'status-pill--req-pendiente_autorizacion_gerencia',
                                            default => 'status-pill--muted',
                                        };
                                        $isPending = $requisition->status === \App\Models\PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA;
                                    @endphp
                                    <tr>
                                        <td>{{ $requisition->code }}</td>
                                        <td><x-date-table :value="$requisition->request_date" /></td>
                                        <td>{{ $requisition->requester?->name }}</td>
                                        <td>{{ $requisition->position?->name }}</td>
                                        <td>{{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}</td>
                                        <td>
                                            <span class="status-pill {{ $estadoPill }}">
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a
                                                href="{{ route('requisitions.management-approval.show', ['module' => $moduleKey, 'requisition' => $requisition]) }}"
                                                class="btn btn--{{ $isPending ? 'primary' : 'secondary' }} btn--sm"
                                            >
                                                {{ $isPending ? 'Autorizar' : 'Ver detalle' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">
                                            @if ($currentEstado === \App\Services\Requisitions\RequisitionManagementApprovalService::FILTER_PENDIENTE)
                                                No hay requisiciones pendientes de autorizacion.
                                            @else
                                                No hay requisiciones con la vista seleccionada.
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
</x-app-layout>
