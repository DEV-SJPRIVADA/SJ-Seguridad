<div class="indicadores-ftop03-row">
    <div>
        <label class="form-label">Facturacion mensual</label>
        <input type="number" step="0.01" name="form[facturacion_mensual]" value="{{ old('form.facturacion_mensual', $form['facturacion_mensual'] ?? '') }}" class="supply-input js-capture-field" data-field="facturacion_mensual" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Valor pagado siniestros</label>
        <input type="number" step="0.01" name="form[valor_pagado_siniestros]" value="{{ old('form.valor_pagado_siniestros', $form['valor_pagado_siniestros'] ?? '') }}" class="supply-input js-capture-field" data-field="valor_pagado_siniestros" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total servicios</label>
        <input type="number" step="0.01" name="form[total_servicios]" value="{{ old('form.total_servicios', $form['total_servicios'] ?? '') }}" class="supply-input js-capture-field" data-field="total_servicios" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total siniestros</label>
        <input type="number" step="0.01" name="form[total_siniestros]" value="{{ old('form.total_siniestros', $form['total_siniestros'] ?? '') }}" class="supply-input js-capture-field" data-field="total_siniestros" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__action">
        <button type="button" class="btn btn--secondary js-open-classification-modal" @disabled($isPeriodClosed)>
            Clasificar siniestros
        </button>
    </div>
    <div class="indicadores-ftop03-row__action">
        <button type="button" class="btn btn--secondary js-open-improvement-modal" @disabled($isPeriodClosed)>
            Analisis
        </button>
    </div>
</div>
