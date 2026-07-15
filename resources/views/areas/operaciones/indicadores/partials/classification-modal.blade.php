@php
    $classificationRows = old('form.clasificacion_por_tipo', $form['clasificacion_por_tipo'] ?? [['tipo' => '', 'cantidad' => null]]);
    if (! is_array($classificationRows) || count($classificationRows) === 0) {
        $classificationRows = [['tipo' => '', 'cantidad' => null]];
    }
    $showClassification = $openClassificationModal;
@endphp

<div
    id="classification-modal"
    class="indicadores-modal-backdrop{{ $showClassification ? '' : ' is-hidden' }}"
    role="dialog"
    aria-modal="true"
    data-modal="classification"
    @if (! $showClassification) hidden @endif
>
    <div class="panel indicadores-modal" data-modal-panel>
        <div class="panel__header">
            <div class="indicadores-modal__header">
                <h4 class="panel-title">Clasificacion de siniestros</h4>
                <button type="button" class="btn btn--secondary btn--sm js-close-classification-modal">Cerrar</button>
            </div>
        </div>
        <div class="panel__body form-stack">
            <table class="supply-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Tipo de siniestro</th>
                        <th>Cantidad</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-classification-rows>
                    @foreach ($classificationRows as $index => $row)
                        <tr data-classification-row>
                            <td style="text-align:left;">
                                <select name="form[clasificacion_por_tipo][{{ $index }}][tipo]" class="supply-input supply-select js-classification-type">
                                    <option value="">Seleccione...</option>
                                    @foreach ($siniestroOptions as $option)
                                        <option value="{{ $option }}" @selected(($row['tipo'] ?? '') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="form[clasificacion_por_tipo][{{ $index }}][cantidad]" value="{{ $row['cantidad'] ?? '' }}" class="supply-input" />
                            </td>
                            <td>
                                <button type="button" class="btn btn--secondary btn--sm js-remove-classification-row">X</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <template id="classification-row-template">
                <tr data-classification-row>
                    <td style="text-align:left;">
                        <select name="form[clasificacion_por_tipo][__INDEX__][tipo]" class="supply-input supply-select js-classification-type">
                            <option value="">Seleccione...</option>
                            @foreach ($siniestroOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="form[clasificacion_por_tipo][__INDEX__][cantidad]" value="" class="supply-input" />
                    </td>
                    <td>
                        <button type="button" class="btn btn--secondary btn--sm js-remove-classification-row">X</button>
                    </td>
                </tr>
            </template>

            <div class="indicadores-actions indicadores-actions--end">
                <button type="button" class="btn btn--secondary btn--sm js-add-classification-row">Agregar fila</button>
                <button type="button" class="btn btn--secondary btn--sm js-close-classification-modal">Cancelar</button>
                <button type="button" class="btn btn--primary btn--sm js-close-classification-modal">Guardar clasificacion</button>
            </div>
        </div>
    </div>
</div>
