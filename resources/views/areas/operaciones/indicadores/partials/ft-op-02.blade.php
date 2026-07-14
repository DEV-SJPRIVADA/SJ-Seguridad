<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total servicios</label>
        <input type="number" step="0.01" name="form[total_servicios]" value="{{ old('form.total_servicios', $form['total_servicios'] ?? '') }}" class="supply-input js-capture-field" data-field="total_servicios" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">No conformes</label>
        <input type="number" step="0.01" name="form[no_conformes]" value="{{ old('form.no_conformes', $form['no_conformes'] ?? '') }}" class="supply-input js-capture-field" data-field="no_conformes" @disabled($isPeriodClosed) />
    </div>
</div>
