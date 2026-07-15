<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total clientes cadena</label>
        <input type="number" step="0.01" name="form[total_clientes_cadena]" value="{{ old('form.total_clientes_cadena', $form['total_clientes_cadena'] ?? '') }}" class="supply-input js-capture-field" data-field="total_clientes_cadena" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Eventos indeseables</label>
        <input type="number" step="0.01" name="form[eventos_indeseables]" value="{{ old('form.eventos_indeseables', $form['eventos_indeseables'] ?? '') }}" class="supply-input js-capture-field" data-field="eventos_indeseables" @disabled($isPeriodClosed) />
    </div>
</div>
