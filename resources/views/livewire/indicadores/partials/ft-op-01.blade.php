<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Total personal</label>
        <input type="number" step="0.01" wire:model.live="form.total_personal" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Personal capacitado</label>
        <input type="number" step="0.01" wire:model.live="form.personal_capacitado" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
