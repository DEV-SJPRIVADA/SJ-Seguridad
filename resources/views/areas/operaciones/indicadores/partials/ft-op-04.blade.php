<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Supervisiones programadas</label>
        <input type="number" step="0.01" name="form[supervisiones_programadas]" value="{{ old('form.supervisiones_programadas', $form['supervisiones_programadas'] ?? '') }}" class="supply-input js-capture-field" data-field="supervisiones_programadas" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Supervisiones realizadas</label>
        <input type="number" step="0.01" name="form[supervisiones_realizadas]" value="{{ old('form.supervisiones_realizadas', $form['supervisiones_realizadas'] ?? '') }}" class="supply-input js-capture-field" data-field="supervisiones_realizadas" @disabled($isPeriodClosed) />
    </div>
</div>
