<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Gestion de Compras - Suministros</h3>
                    <p class="panel-text">Bandeja para ingresar costos unitarios y completar el proceso de adquisicion.</p>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Items Aprobados</th>
                                    <th>Valor Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr>
                                        <td class="text-center">#{{ $request->id }}</td>
                                        <td class="text-center">{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td class="text-center">{{ $request->user->name }}</td>
                                        <td class="text-center">{{ $request->items->where('approved_quantity', '>', 0)->count() }} productos</td>
                                        <td class="text-center">
                                            <span style="font-weight: 700; color: var(--color-success);">
                                                {{ $request->total_cost ? '$' . number_format($request->total_cost, 2) : '---' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ str_replace('_', ' ', ucfirst($request->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($request->status !== 'completada')
                                                <a href="{{ route('supplies.purchasing.edit', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--secondary btn--sm">
                                                    Ingresar Costos
                                                </a>
                                            @else
                                                <a href="{{ route('supplies.show', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--info btn--sm">
                                                    Ver Detalle
                                                </a>
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
