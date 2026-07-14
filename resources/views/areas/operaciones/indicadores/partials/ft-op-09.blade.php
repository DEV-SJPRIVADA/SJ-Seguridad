<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Armas programadas</label>
        <input type="number" step="0.01" name="form[armas_programadas]" value="{{ old('form.armas_programadas', $form['armas_programadas'] ?? '') }}" class="supply-input js-capture-field" data-field="armas_programadas" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Armas inspeccionadas</label>
        <input type="number" step="0.01" name="form[armas_inspeccionadas]" value="{{ old('form.armas_inspeccionadas', $form['armas_inspeccionadas'] ?? '') }}" class="supply-input js-capture-field" data-field="armas_inspeccionadas" @disabled($isPeriodClosed) />
    </div>
</div>
