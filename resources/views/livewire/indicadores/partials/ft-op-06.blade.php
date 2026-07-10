<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total clientes cadena</label>
        <input type="number" step="0.01" wire:model.live="form.total_clientes_cadena" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Eventos indeseables</label>
        <input type="number" step="0.01" wire:model.live="form.eventos_indeseables" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
