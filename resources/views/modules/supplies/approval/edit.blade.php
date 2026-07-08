<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Aprobacion de Solicitud #{{ $request->id }}</h3>
                    <p class="panel-text">Solicitado por <strong>{{ $request->user->name }}</strong> el {{ $request->created_at->format('d/m/Y') }}.</p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('supplies.approval.update', ['module' => $module, 'supply_request' => $request->id]) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="card card--muted block-spaced">
                            <p class="text-caption">Observaciones del solicitante</p>
                            <p>{{ $request->observations ?: 'Sin observaciones.' }}</p>
                        </div>

                        <div class="block-spaced">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Inventario Actual</th>
                                        <th>Cantidad Solicitada</th>
                                        <th style="width: 200px;">Cantidad Autorizada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($request->items as $item)
                                        <tr>
                                            <td style="font-weight: 600; color: var(--color-primary);">
                                                {{ $item->displayName() }}
                                                @if($item->is_not_in_catalog)
                                                    <span class="status-pill status-pill--warning" style="margin-left: 0.5rem;">Fuera de catalogo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($item->is_not_in_catalog)
                                                    <span class="text-muted">N/A</span>
                                                @else
                                                    <span class="status-pill status-pill--muted">{{ $item->current_inventory }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->requested_quantity }}</td>
                                            <td>
                                                <input type="number" name="items[{{ $item->id }}][approved_quantity]"
                                                    class="supply-input"
                                                    value="{{ $item->requested_quantity }}"
                                                    min="0" required>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="form-field block-spaced">
                            <label class="form-label" for="quality_observations">Observaciones de aprobacion (opcional)</label>
                            <textarea
                                id="quality_observations"
                                name="quality_observations"
                                class="form-textarea"
                                rows="3"
                                placeholder="Justifica cualquier ajuste en las cantidades..."
                            >{{ old('quality_observations') }}</textarea>
                        </div>

                        <div class="form-actions">
                            <p class="text-small text-muted">Al aprobar, las cantidades autorizadas quedan listas para exportar en Insumos aprobados.</p>
                            <div class="form-actions__group">
                                <button type="submit" name="action" value="reject" class="btn btn--danger">
                                    Rechazar solicitud
                                </button>
                                <button type="submit" name="action" value="approve" class="btn btn--primary">
                                    Aprobar solicitud
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
