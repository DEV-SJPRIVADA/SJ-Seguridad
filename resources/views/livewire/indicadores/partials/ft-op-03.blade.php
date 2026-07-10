<div class="indicadores-ftop03-row">
    <div>
        <label class="form-label">Facturacion mensual</label>
        <input type="number" step="0.01" wire:model.live="form.facturacion_mensual" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div>
        <label class="form-label">Valor pagado siniestros</label>
        <input type="number" step="0.01" wire:model.live="form.valor_pagado_siniestros" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total servicios</label>
        <input type="number" step="0.01" wire:model.live="form.total_servicios" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__narrow">
        <label class="form-label">Total siniestros</label>
        <input type="number" step="0.01" wire:model.live="form.total_siniestros" class="supply-input" @disabled($isPeriodClosed) />
    </div>
    <div class="indicadores-ftop03-row__action">
        <button type="button" wire:click="openClassificationModal" class="btn btn--secondary" @disabled($isPeriodClosed)>
            Clasificar siniestros
        </button>
    </div>
    <div class="indicadores-ftop03-row__action">
        <button type="button" wire:click="openImprovementModal" class="btn btn--secondary" @disabled($isPeriodClosed)>
            Analisis
        </button>
    </div>
</div>
