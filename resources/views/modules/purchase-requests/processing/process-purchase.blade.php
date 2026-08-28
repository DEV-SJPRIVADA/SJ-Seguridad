<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Procesar solicitud de compra {{ $purchaseRequest->folio() }}</h3>
                    <p class="panel-text">
                        Solicitante: <strong>{{ $purchaseRequest->user?->name ?? '—' }}</strong>
                        · Area: <strong>{{ $purchaseRequest->areaLabel() ?? '—' }}</strong>
                    </p>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cantidad</th>
                                    <th>Descripcion</th>
                                    <th>Referencia</th>
                                    <th>Utilizacion</th>
                                    <th>Ubicacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseRequest->items as $item)
                                    <tr>
                                        <td class="text-center">{{ $item->orden ?? $loop->iteration }}</td>
                                        <td class="text-center">{{ $item->cantidad }}</td>
                                        <td style="font-weight: 600; color: var(--color-primary);">{{ $item->descripcion }}</td>
                                        <td>{{ $item->referencia }}</td>
                                        <td>{{ $item->utilizacion }}</td>
                                        <td>{{ $item->ubicacion }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form action="{{ route('purchase-requests.processing.purchase.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="dashboard-stat-grid block-spaced">
                            <div class="form-field">
                                <label class="form-label" for="estado_compras">Estado de compras</label>
                                <x-searchable-select
                                    id="estado_compras"
                                    name="estado_compras"
                                    :options="\App\Models\PurchaseRequest::estadosComprasLabels()"
                                    :value="old('estado_compras', $purchaseRequest->estado_compras)"
                                    placeholder="Seleccione estado…"
                                    searchPlaceholder="Buscar estado…"
                                    :required="true"
                                    :allowClear="false"
                                />
                                <x-input-error :messages="$errors->get('estado_compras')" />
                            </div>
                        </div>

                        <div class="form-field block-spaced">
                            <label class="form-label" for="comentarios_compras">Comentarios</label>
                            <textarea name="comentarios_compras" id="comentarios_compras" class="form-textarea" rows="3" placeholder="Observaciones del area de compras...">{{ old('comentarios_compras', $purchaseRequest->comentarios_compras) }}</textarea>
                            <x-input-error :messages="$errors->get('comentarios_compras')" />
                        </div>

                        <div class="form-actions">
                            <div class="form-actions__group">
                                <a href="{{ route('purchase-requests.processing.index', ['module' => $module]) }}" class="btn btn--secondary">
                                    Volver a la bandeja
                                </a>
                                <button type="submit" class="btn btn--primary">
                                    Guardar procesamiento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
