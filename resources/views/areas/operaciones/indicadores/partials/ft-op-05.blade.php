<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Visitas programadas</label>
        <input type="number" step="0.01" name="form[visitas_programadas]" value="{{ old('form.visitas_programadas', $form['visitas_programadas'] ?? '') }}" class="supply-input js-capture-field" data-field="visitas_programadas" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Visitas realizadas</label>
        <input type="number" step="0.01" name="form[visitas_realizadas]" value="{{ old('form.visitas_realizadas', $form['visitas_realizadas'] ?? '') }}" class="supply-input js-capture-field" data-field="visitas_realizadas" @disabled($isPeriodClosed) />
    </div>
</div>
