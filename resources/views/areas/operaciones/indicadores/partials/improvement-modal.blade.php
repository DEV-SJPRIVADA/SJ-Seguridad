@php
    $showImprovement = $openImprovementModal || $errors->has('improvement.analysis') || $errors->has('improvement.action_taken') || $errors->has('improvement.action_defined') || $errors->has('improvement.improvement_required');
@endphp

<div
    id="improvement-modal"
    class="indicadores-modal-backdrop{{ $showImprovement ? '' : ' is-hidden' }}"
    role="dialog"
    aria-modal="true"
    data-modal="improvement"
    @if (! $showImprovement) hidden @endif
>
    <div class="panel indicadores-modal" data-modal-panel>
        <div class="panel__header">
            <div class="indicadores-modal__header">
                <h4 class="panel-title">Analisis de resultados (obligatorio)</h4>
                <button type="button" class="btn btn--secondary btn--sm js-close-improvement-modal">Cerrar</button>
            </div>
        </div>
        <div class="panel__body form-stack">
            <div class="indicadores-modal-fields">
                <div>
                    <label class="form-label">Analisis</label>
                    <textarea name="improvement[analysis]" rows="5" class="supply-textarea">{{ old('improvement.analysis', $improvementAnalysis) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Accion tomada</label>
                    <textarea name="improvement[action_taken]" rows="5" class="supply-textarea">{{ old('improvement.action_taken', $improvementActionTaken) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Accion definida</label>
                    <textarea name="improvement[action_defined]" rows="5" class="supply-textarea">{{ old('improvement.action_defined', $improvementActionDefined) }}</textarea>
                </div>
                <div data-improvement-required-wrap @if ($complies) hidden @endif>
                    <label class="form-label">Debe agregar mejora</label>
                    <textarea name="improvement[improvement_required]" rows="5" class="supply-textarea indicadores-textarea--warning" placeholder="Describe la mejora requerida porque no se cumplio la meta...">{{ old('improvement.improvement_required', $improvementRequired) }}</textarea>
                </div>
            </div>
            <div class="indicadores-actions indicadores-actions--end">
                <button type="button" class="btn btn--secondary btn--sm js-close-improvement-modal">Cancelar</button>
                <button type="submit" class="btn btn--primary btn--sm" @disabled($isPeriodClosed)>
                    Guardar mes
                </button>
            </div>
        </div>
    </div>
</div>
