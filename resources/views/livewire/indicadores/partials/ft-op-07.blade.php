<div class="indicadores-field-grid">
    <div>
        <label class="form-label">Analisis programados</label>
        <input type="number" step="0.01" wire:model.live="form.analisis_programados" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Analisis realizados</label>
        <input type="number" step="0.01" wire:model.live="form.analisis_realizados" class="supply-input" @disabled($isPeriodClosed) />
    </div>
</div>
