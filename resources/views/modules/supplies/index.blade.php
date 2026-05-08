<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Mis Solicitudes de Suministros</h3>
                            <p class="panel-text">Historial de pedidos realizados por tu usuario para el area seleccionada.</p>
                        </div>
                        <a href="{{ route('supplies.create', ['module' => $module]) }}" class="btn btn--primary">
                            Nueva Solicitud
                        </a>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Items</th>
                                    <th>Costo Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr>
                                        <td class="text-center">#{{ $request->id }}</td>
                                        <td class="text-center">{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td class="text-center">
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ str_replace('_', ' ', ucfirst($request->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $request->items->count() }} productos</td>
                                        <td class="text-center">
                                            <span style="font-weight: 600;">{{ $request->total_cost ? '$' . number_format($request->total_cost, 2) : '---' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('supplies.show', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--secondary btn--sm">
                                                Ver Detalle
                                            </a>
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
