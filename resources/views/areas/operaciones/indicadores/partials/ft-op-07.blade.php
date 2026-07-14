<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Analisis programados</label>
        <input type="number" step="0.01" name="form[analisis_programados]" value="{{ old('form.analisis_programados', $form['analisis_programados'] ?? '') }}" class="supply-input js-capture-field" data-field="analisis_programados" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Analisis realizados</label>
        <input type="number" step="0.01" name="form[analisis_realizados]" value="{{ old('form.analisis_realizados', $form['analisis_realizados'] ?? '') }}" class="supply-input js-capture-field" data-field="analisis_realizados" @disabled($isPeriodClosed) />
    </div>
</div>
