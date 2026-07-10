<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Armas programadas</label>
        <input type="number" step="0.01" wire:model.live="form.armas_programadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Armas inspeccionadas</label>
        <input type="number" step="0.01" wire:model.live="form.armas_inspeccionadas" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
