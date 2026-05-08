<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Costeo de Solicitud #{{ $request->id }}</h3>
                    <p class="panel-text">Ingresa el costo unitario de cada producto autorizado para finalizar la solicitud.</p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('supplies.purchasing.update', ['module' => $module, 'supply_request' => $request->id]) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="dashboard-stat-grid bottom-spaced">
                            <article class="card card--muted">
                                <p class="text-caption">Solicitante</p>
                                <p class="panel-title">{{ $request->user->name }}</p>
                            </article>
                            <article class="card card--muted">
                                <p class="text-caption">Area</p>
                                <p class="panel-title">{{ config("access.areas.{$request->area_key}") }}</p>
                            </article>
                            <article class="card card--success">
                                <p class="text-caption">Revision Calidad</p>
                                <p class="panel-title">Aprobado</p>
                            </article>
                        </div>

                        <div class="card card--info block-spaced">
                            <p class="text-caption">Observaciones de Calidad</p>
                            <p>{{ $request->quality_observations ?: 'Sin observaciones técnicas.' }}</p>
                        </div>

                        <div class="block-spaced">
                            <table class="supply-table" id="purchasing-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cant. Autorizada</th>
                                        <th style="width: 180px;">Costo Unitario ($)</th>
                                        <th style="width: 200px;">Novedades/Observaciones</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($request->items as $item)
                                        @if($item->approved_quantity > 0)
                                            <tr>
                                                <td style="font-weight: 600; color: var(--color-primary);">{{ $item->product->name }}</td>
                                                <td class="text-center">
                                                    <span class="status-pill status-pill--muted">{{ $item->approved_quantity }}</span>
                                                </td>
                                                <td>
                                                    <div class="currency-input-wrap">
                                                        <input type="number" step="0.01" name="items[{{ $item->id }}][unit_cost]" 
                                                            class="supply-input unit-cost-input" 
                                                            data-qty="{{ $item->approved_quantity }}"
                                                            value="{{ $item->unit_cost ?: '' }}" 
                                                            min="0" required>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $item->id }}][purchasing_observations]" 
                                                        class="supply-input" 
                                                        placeholder="Ej: Marca alternativa..."
                                                        value="{{ $item->purchasing_observations }}">
                                                </td>
                                                <td class="text-center">
                                                    <span class="item-subtotal" style="font-weight: 700;">$0.00</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right" style="font-weight: 700; padding: 1rem !important; text-align: right !important;">VALOR TOTAL:</td>
                                        <td class="text-center" style="font-weight: 800; color: var(--color-success); font-size: 1.1rem; padding: 1rem !important;">
                                            <span id="grand-total">$0.00</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                            <a href="{{ route('supplies.purchasing.index', ['module' => $module]) }}" class="btn btn--secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn--primary">
                                Guardar Costos y Completar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            function calculateTotals() {
                let grandTotal = 0;
                $('.unit-cost-input').each(function() {
                    const cost = parseFloat($(this).val()) || 0;
                    const qty = parseFloat($(this).data('qty')) || 0;
                    const subtotal = cost * qty;
                    grandTotal += subtotal;
                    $(this).closest('tr').find('.item-subtotal').text('$' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
                });
                $('#grand-total').text('$' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2}));
            }

            $('.unit-cost-input').on('input', function() {
                calculateTotals();
            });

            calculateTotals();
        });
    </script>
</x-app-layout>
