<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total servicios</label>
        <input type="number" step="0.01" wire:model.live="form.total_servicios" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">No conformes</label>
        <input type="number" step="0.01" wire:model.live="form.no_conformes" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
