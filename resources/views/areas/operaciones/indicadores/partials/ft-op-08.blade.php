<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Inventarios programados</label>
        <input type="number" step="0.01" name="form[inventarios_programados]" value="{{ old('form.inventarios_programados', $form['inventarios_programados'] ?? '') }}" class="supply-input js-capture-field" data-field="inventarios_programados" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Inventarios realizados</label>
        <input type="number" step="0.01" name="form[inventarios_realizados]" value="{{ old('form.inventarios_realizados', $form['inventarios_realizados'] ?? '') }}" class="supply-input js-capture-field" data-field="inventarios_realizados" @disabled($isPeriodClosed) />
    </div>
</div>
