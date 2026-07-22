<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Metas por indicador</h4>
    <p class="indicadores-subpanel__text">Define el operador de comparacion, la meta y el umbral critico de cada FT-OP. Los valores se reflejan en el listado de captura, la ficha y el semaforo de cumplimiento. <strong>FT-OP-03 (Compuesto):</strong> meta = frecuencia maxima (A); critico = impacto economico maximo (B). No usa un solo operador.</p>

    <form method="POST" action="{{ route('indicadores.admin.metas.update') }}" class="indicadores-form-compact">
        @csrf
        @method('PATCH')

        <div class="indicadores-table-wrap">
            <table class="supply-table indicadores-table indicadores-table--metas">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Operador</th>
                        <th>Meta (%)</th>
                        <th>Critico (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($indicators as $indicator)
                        <tr>
                            <td><span class="indicadores-code">{{ $indicator->code }}</span> {{ $indicator->name }}</td>
                            <td>
                                @if ($indicator->usesCompositeTarget())
                                    <span class="indicadores-meta-hint" title="{{ $indicator->compositeTargetHint() }}">Compuesto</span>
                                    <input type="hidden" name="operators[{{ $indicator->id }}]" value="{{ old('operators.'.$indicator->id, $indicator->target_operator) }}">
                                @else
                                    <select name="operators[{{ $indicator->id }}]" class="supply-input indicadores-input-operator" aria-label="Operador {{ $indicator->code }}">
                                        @foreach (['>=', '<=', '=='] as $operator)
                                            <option value="{{ $operator }}" @selected(old('operators.'.$indicator->id, $indicator->target_operator) === $operator)>
                                                {{ $operator }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
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
