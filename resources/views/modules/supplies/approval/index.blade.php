<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Aprobacion de Insumos</h3>
                    <p class="panel-text">Solicitudes pendientes de aprobacion y ajuste de cantidades.</p>
                    <div style="margin-top:0.5rem;">
                        <x-export-excel route="{{ route('supplies.approval.export', ['module' => $module]) }}" />
                    </div>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable" data-no-excel>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Area</th>
                                    <th>Sede</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $request->user->name }}</td>
                                        <td>{{ config("access.areas.{$request->area_key}") }}</td>
                                        <td>{{ $request->site_utilization ?? '—' }}</td>
                                        <td>{{ $request->items->count() }} productos</td>
                                        <td>
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            @if($request->status === 'pendiente_calidad')
                                                <a href="{{ route('supplies.approval.edit', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--secondary btn--sm">
                                                    Revisar
                                                </a>
                                            @else
                                                <span class="text-muted">Procesada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">No hay solicitudes pendientes de aprobacion.</td>
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
