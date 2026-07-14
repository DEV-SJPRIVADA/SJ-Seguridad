<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total personal</label>
        <input type="number" step="0.01" name="form[total_personal]" value="{{ old('form.total_personal', $form['total_personal'] ?? '') }}" class="supply-input js-capture-field" data-field="total_personal" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Personal capacitado</label>
        <input type="number" step="0.01" name="form[personal_capacitado]" value="{{ old('form.personal_capacitado', $form['personal_capacitado'] ?? '') }}" class="supply-input js-capture-field" data-field="personal_capacitado" @disabled($isPeriodClosed) />
    </div>
</div>
