<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Supervisiones programadas</label>
        <input type="number" step="0.01" wire:model.live="form.supervisiones_programadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Supervisiones realizadas</label>
        <input type="number" step="0.01" wire:model.live="form.supervisiones_realizadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
