<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Pesos del dashboard</h4>
    <p class="indicadores-subpanel__text">Distribucion ponderada para el score global (debe sumar 100%).</p>

    <form method="POST" action="{{ route('indicadores.admin.weights.update') }}" class="indicadores-form-compact">
        @csrf
        @method('PATCH')

        <div class="indicadores-table-wrap">
            <table class="supply-table indicadores-table indicadores-table--weights">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Peso (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($indicators as $indicator)
                        <tr>
                            <td><span class="indicadores-code">{{ $indicator->code }}</span> {{ $indicator->name }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100"
                                       name="weights[{{ $indicator->id }}]"
                                       class="supply-input indicadores-input-narrow"
                                       value="{{ old('weights.'.$indicator->id, $indicator->dashboardWeight?->weight ?? 0) }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="indicadores-filter-bar indicadores-filter-bar--stacked" style="margin-top:1rem;">
            <div class="indicadores-field indicadores-field--lg">
                <label class="form-label">Motivo del cambio</label>
                <input type="text" name="reason" class="supply-input" required value="{{ old('reason') }}">
            </div>
            <div class="indicadores-field indicadores-field--action">
                <button type="submit" class="btn btn--primary btn--sm">Guardar pesos</button>
            </div>
        </div>
    </form>
</div>
