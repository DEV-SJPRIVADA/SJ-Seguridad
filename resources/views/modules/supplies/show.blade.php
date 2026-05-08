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
                            <h3 class="panel-title">Detalle de Solicitud #{{ $request->id }}</h3>
                            <p class="panel-text">Estado actual: 
                                <span class="status-pill status-pill--req-{{ $request->status }}">
                                    {{ str_replace('_', ' ', ucfirst($request->status)) }}
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('supplies.index', ['module' => $module]) }}" class="btn btn--secondary">
                            Volver al listado
                        </a>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="dashboard-stat-grid bottom-spaced">
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de Solicitud</p>
                            <p class="panel-title">{{ $request->created_at->format('d/m/Y H:i') }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitante</p>
                            <p class="panel-title">{{ $request->user->name }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Area</p>
                            <p class="panel-title">{{ config("access.areas.{$request->area_key}") }}</p>
                        </article>
                    </div>

                    @if($request->quality_observations)
                        <div class="card card--info block-spaced">
                            <p class="text-caption">Observaciones de Calidad</p>
                            <p>{{ $request->quality_observations }}</p>
                            <p class="text-small text-muted" style="margin-top: 0.5rem;">Revisado por: {{ $request->qualityReviewer->name ?? 'N/A' }}</p>
                        </div>
                    @endif

                    <div class="block-spaced">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Inventario Reportado</th>
                                    <th>Cant. Solicitada</th>
                                    <th>Cant. Autorizada</th>
                                    <th>Novedades Compras</th>
                                    <th>Estado Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($request->items as $item)
                                    <tr>
                                        <td style="font-weight: 600; color: var(--color-primary);">{{ $item->product->name }}</td>
                                        <td class="text-center">
                                            <span class="status-pill status-pill--muted">{{ $item->current_inventory }}</span>
                                        </td>
                                        <td class="text-center">{{ $item->requested_quantity }}</td>
                                        <td class="text-center">
                                            <span style="font-weight: 700;">{{ $item->approved_quantity ?? '---' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-small">{{ $item->purchasing_observations ?: '---' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($request->status === 'rechazada_calidad')
                                                <span class="status-pill status-pill--danger">No autorizado</span>
                                            @elseif($item->approved_quantity === 0 && $request->status !== 'pendiente_calidad')
                                                <span class="status-pill status-pill--danger">Cancelado</span>
                                            @elseif($item->approved_quantity > 0)
                                                <span class="status-pill status-pill--success">Autorizado</span>
                                            @else
                                                <span class="status-pill status-pill--info">Pendiente</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($request->observations)
                        <div class="block-spaced-lg">
                            <h4 class="form-label">Notas del Solicitante:</h4>
                            <p class="text-muted">{{ $request->observations }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
