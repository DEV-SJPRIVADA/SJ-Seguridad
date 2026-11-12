{{-- Decisión del aprobador: aprobar (comentario opcional) o rechazar (comentario obligatorio; la orden queda anulada). --}}
<form method="POST" action="{{ $decideUrl }}" class="pur-review-decision">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <header class="pur-review-decision__head">
        <p class="pur-review-decision__eyebrow">Su decisión</p>
        <h3 class="pur-review-decision__title">Aprobar o rechazar la orden</h3>
        <p class="pur-review-decision__lead">
            Si la orden es correcta, apruébela. Si encuentra un problema, rechácela indicando el motivo.
        </p>
    </header>

    <div class="pur-review-decision__field">
        <label class="pur-review-decision__label" for="comentario_aprobacion">
            Comentario
            <span class="pur-review-decision__label-hint">obligatorio si rechaza</span>
        </label>
        <textarea
            id="comentario_aprobacion"
            name="comentario_aprobacion"
            class="form-textarea pur-review-decision__textarea"
            rows="3"
            maxlength="5000"
            placeholder="Escriba el motivo del rechazo o una observación para Compras."
        >{{ old('comentario_aprobacion') }}</textarea>
        <x-input-error :messages="$errors->get('comentario_aprobacion')" />
        <x-input-error :messages="$errors->get('decision')" />
    </div>

    <p class="pur-review-decision__warning">
        <x-lucide-circle-alert width="15" height="15" aria-hidden="true" />
        <span>Si rechaza, la orden queda <strong>anulada</strong> y su cantidad vuelve al saldo del pedido.</span>
    </p>

    <div class="pur-review-decision__actions">
        <button type="submit" name="decision" value="aprobar" class="btn btn--primary">
            <x-lucide-circle-check width="16" height="16" aria-hidden="true" />
            Aprobar orden
        </button>
        <button type="submit" name="decision" value="rechazar" class="btn btn--secondary pur-review-decision__reject">
            <x-lucide-x width="16" height="16" aria-hidden="true" />
            Rechazar
        </button>
    </div>
</form>
