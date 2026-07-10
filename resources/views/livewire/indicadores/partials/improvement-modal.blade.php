@if ($showImprovementModal)
    <div
        class="indicadores-modal-backdrop"
        wire:key="improvement-modal"
        role="dialog"
        aria-modal="true"
        wire:click.self="closeImprovementModal"
    >
        <div class="panel indicadores-modal" wire:click.stop>
            <div class="panel__header">
                <div class="indicadores-modal__header">
                    <h4 class="panel-title">Analisis de resultados (obligatorio)</h4>
                    <button type="button" wire:click="closeImprovementModal" class="btn btn--secondary btn--sm">Cerrar</button>
                </div>
            </div>
            <div class="panel__body form-stack">
                @if ($errors->any())
                    <div class="panel indicadores-alert indicadores-alert--error" style="margin:0;">
                        <div class="panel__body text-small">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! $selectedOperationsLeaderId)
                    <div class="panel indicadores-alert indicadores-alert--error" style="margin:0;">
                        <div class="panel__body text-small">
                            Debes seleccionar un jefe de operaciones en los filtros superiores antes de guardar.
                        </div>
                    </div>
                @endif

                <div class="indicadores-modal-fields">
                    <div>
                        <label class="form-label">Analisis</label>
                        <textarea wire:model.defer="improvementAnalysis" rows="5" class="supply-textarea"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Accion tomada</label>
                        <textarea wire:model.defer="improvementActionTaken" rows="5" class="supply-textarea"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Accion definida</label>
                        <textarea wire:model.defer="improvementActionDefined" rows="5" class="supply-textarea"></textarea>
                    </div>
                    @if (! $complies)
                        <div>
                            <label class="form-label">Debe agregar mejora</label>
                            <textarea wire:model.defer="improvementRequired" rows="5" class="supply-textarea indicadores-textarea--warning" placeholder="Describe la mejora requerida porque no se cumplio la meta..."></textarea>
                        </div>
                    @endif
                </div>
                <div class="indicadores-actions indicadores-actions--end">
                    <button type="button" wire:click="closeImprovementModal" class="btn btn--secondary">Cancelar</button>
                    <button type="button" wire:click="save" class="btn btn--primary" wire:loading.attr="disabled" wire:target="save" @disabled($isPeriodClosed || ! $selectedOperationsLeaderId)>
                        <span wire:loading.remove wire:target="save">Guardar mes</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
