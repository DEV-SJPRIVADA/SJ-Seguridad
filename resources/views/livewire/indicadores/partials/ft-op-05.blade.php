<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Visitas programadas</label>
        <input type="number" step="0.01" wire:model.live="form.visitas_programadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Visitas realizadas</label>
        <input type="number" step="0.01" wire:model.live="form.visitas_realizadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
