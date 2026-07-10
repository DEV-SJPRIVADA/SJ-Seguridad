<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Inventarios programados</label>
        <input type="number" step="0.01" wire:model.live="form.inventarios_programados" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Inventarios realizados</label>
        <input type="number" step="0.01" wire:model.live="form.inventarios_realizados" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
