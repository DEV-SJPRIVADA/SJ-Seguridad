<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Revision de Suministros (Calidad)</h3>
                    <p class="panel-text">Listado de solicitudes pendientes de aprobacion técnica y ajuste de cantidades.</p>
                </div>

                <div class="panel__body">
                    <div class="data-table-wrap">
                        <table class="data-table js-datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Area</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $request->user->name }}</td>
                                        <td>{{ config("access.areas.{$request->area_key}") }}</td>
                                        <td>{{ $request->items->count() }} productos</td>
                                        <td>
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ str_replace('_', ' ', ucfirst($request->status)) }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            @if($request->status === 'pendiente_calidad')
                                                <a href="{{ route('supplies.quality.edit', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--secondary btn--sm">
                                                    Revisar
                                                </a>
                                            @else
                                                <span class="text-muted">Procesada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
