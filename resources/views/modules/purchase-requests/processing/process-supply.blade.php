<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Procesar suministro #{{ $supplyRequest->id }}</h3>
                    <p class="panel-text">
                        Solicitante: <strong>{{ $supplyRequest->user?->name ?? '—' }}</strong>
                        · Area: <strong>{{ config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key) }}</strong>
                    </p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('purchase-requests.processing.supply.update', ['module' => $module, 'supply_request' => $supplyRequest->id]) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="block-spaced">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cant. autorizada</th>
                                        <th style="width: 180px;">Costo unitario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplyRequest->items as $item)
                                        <tr>
                                            <td style="font-weight: 600; color: var(--color-primary);">
                                                {{ $item->displayName() }}
                                                @if ($item->is_not_in_catalog)
                                                    <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Fuera de catalogo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->approved_quantity ?? $item->requested_quantity }}</td>
                                            <td>
                                                <input
                                                    type="number"
                                                    name="items[{{ $item->id }}][unit_cost]"
                                                    class="supply-input"
                                                    step="0.01"
                                                    min="0"
                                                    value="{{ old('items.'.$item->id.'.unit_cost', $item->unit_cost) }}"
                                                    required
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="form-actions">
                            <div class="form-actions__group">
                                <a href="{{ route('purchase-requests.processing.index', ['module' => $module]) }}" class="btn btn--secondary">
                                    Volver a la bandeja
                                </a>
                                <button type="submit" name="action" value="complete" class="btn btn--primary">
                                    Completar procesamiento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
