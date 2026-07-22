<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Metas por indicador</h4>
    <p class="indicadores-subpanel__text">Define la meta y el umbral critico de cada FT-OP. Los valores se reflejan en la ficha de captura y en el calculo de cumplimiento.</p>

    <form method="POST" action="{{ route('indicadores.admin.metas.update') }}" class="indicadores-form-compact">
        @csrf
        @method('PATCH')

        <div class="indicadores-table-wrap">
            <table class="supply-table indicadores-table indicadores-table--metas">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Meta (%)</th>
                        <th>Critico (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($indicators as $indicator)
                        <tr>
                            <td><span class="indicadores-code">{{ $indicator->code }}</span> {{ $indicator->name }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="999.99"
                                       name="metas[{{ $indicator->id }}]"
                                       class="supply-input indicadores-input-narrow"
                                       value="{{ old('metas.'.$indicator->id, $indicator->target_value) }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="999.99"
                                       name="critical[{{ $indicator->id }}]"
                                       class="supply-input indicadores-input-narrow"
                                       value="{{ old('critical.'.$indicator->id, $indicator->critical_value ?? 0) }}">
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
                <button type="submit" class="btn btn--primary btn--sm">Guardar metas</button>
            </div>
        </div>
    </form>
</div>
