{{-- Decisión del director: aprobar la anulación (la orden queda anulada) o rechazarla (la orden sigue vigente). --}}
<form method="POST" action="{{ $decideUrl }}" class="pur-review-decision pur-review-decision--danger">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <header class="pur-review-decision__head">
        <p class="pur-review-decision__eyebrow">Su decisión</p>
        <h3 class="pur-review-decision__title">Aprobar o rechazar la anulación</h3>
        <p class="pur-review-decision__lead">
            Revise el motivo que indicó Compras. Si aprueba, la orden se anula; si rechaza, la orden sigue vigente.
        </p>
    </header>

    <div class="pur-review-decision__field">
        <label class="pur-review-decision__label" for="comentario_anulacion">
            Comentario
            <span class="pur-review-decision__label-hint">obligatorio si rechaza</span>
        </label>
        <textarea
            id="comentario_anulacion"
            name="comentario_anulacion"
            class="form-textarea pur-review-decision__textarea"
            rows="3"
            maxlength="5000"
            placeholder="Escriba el motivo del rechazo o una observación para Compras."
        >{{ old('comentario_anulacion') }}</textarea>
        <x-input-error :messages="$errors->get('comentario_anulacion')" />
        <x-input-error :messages="$errors->get('decision')" />
    </div>

    <p class="pur-review-decision__warning">
        <x-lucide-circle-alert width="15" height="15" aria-hidden="true" />
        <span>Si aprueba, la orden queda <strong>anulada</strong> y su cantidad vuelve al saldo del pedido.</span>
    </p>

    <div class="pur-review-decision__actions">
        <button type="submit" name="decision" value="aprobar" class="btn btn--danger">
            <x-lucide-ban width="16" height="16" aria-hidden="true" />
            Aprobar anulación
        </button>
        <button type="submit" name="decision" value="rechazar" class="btn btn--secondary">
            <x-lucide-undo-2 width="16" height="16" aria-hidden="true" />
            Rechazar
        </button>
    </div>
</form>
