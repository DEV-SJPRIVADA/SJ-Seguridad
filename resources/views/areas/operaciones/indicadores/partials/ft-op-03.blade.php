<div class="indicadores-ftop03-row">
    <div>
        <label class="form-label">Facturacion mensual</label>
        <input type="number" step="0.01" name="form[facturacion_mensual]" value="{{ old('form.facturacion_mensual', $form['facturacion_mensual'] ?? '') }}" class="supply-input js-capture-field" data-field="facturacion_mensual" @disabled($isPeriodClosed || ($readOnly ?? false)) />
    </div>
    <div>
        <label class="form-label">Valor pagado siniestros</label>
        <input type="number" step="0.01" name="form[valor_pagado_siniestros]" value="{{ old('form.valor_pagado_siniestros', $form['valor_pagado_siniestros'] ?? '') }}" class="supply-input js-capture-field" data-field="valor_pagado_siniestros" @disabled($isPeriodClosed || ($readOnly ?? false)) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total servicios</label>
        <input type="number" step="0.01" name="form[total_servicios]" value="{{ old('form.total_servicios', $form['total_servicios'] ?? '') }}" class="supply-input js-capture-field" data-field="total_servicios" @disabled($isPeriodClosed || ($readOnly ?? false)) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total siniestros</label>
        <input type="number" step="0.01" name="form[total_siniestros]" value="{{ old('form.total_siniestros', $form['total_siniestros'] ?? '') }}" class="supply-input js-capture-field" data-field="total_siniestros" @disabled($isPeriodClosed || ($readOnly ?? false)) />
    </div>
    <div class="indicadores-ftop03-row__action">
        @if ($readOnly ?? false)
            @if (($form['total_siniestros'] ?? 0) >= 1)
                <button type="button" class="btn btn--secondary btn--sm js-open-classification-modal">
                    Ver clasificacion
                </button>
            @endif
        @else
            <button type="button" class="btn btn--secondary btn--sm js-open-classification-modal" @disabled($isPeriodClosed)>
                Clasificar siniestros
            </button>
        @endif
    </div>
    <div class="indicadores-ftop03-row__action">
        @if ($readOnly ?? false)
            @if ($improvementId || trim((string) ($improvementAnalysis ?? '')) !== '')
                <button type="button" class="btn btn--secondary btn--sm js-open-improvement-modal">
                    Ver analisis
                </button>
            @endif
        @else
            <button type="button" class="btn btn--secondary btn--sm js-open-improvement-modal" @disabled($isPeriodClosed)>
                Analisis
            </button>
        @endif
    </div>
</div>
