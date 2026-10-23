{{-- Decisión del aprobador: aprobar (comentario opcional) o rechazar (comentario obligatorio; la orden queda anulada). --}}
<form method="POST" action="{{ $decideUrl }}" class="req-approval-letter__form">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="form-field">
        <x-input-label for="comentario_aprobacion" value="Comentario (obligatorio si rechaza)" />
        <textarea
            id="comentario_aprobacion"
            name="comentario_aprobacion"
            class="form-textarea"
            rows="3"
            maxlength="5000"
            placeholder="Obligatorio si rechaza. Opcional al aprobar."
        >{{ old('comentario_aprobacion') }}</textarea>
        <x-input-error :messages="$errors->get('comentario_aprobacion')" />
        <x-input-error :messages="$errors->get('decision')" />
    </div>

    <p class="req-approval-letter__hint">Si rechaza, la orden queda anulada y su cantidad vuelve al saldo del pedido.</p>

    <div class="req-approval-letter__actions">
        <button type="submit" name="decision" value="aprobar" class="btn btn--primary">
            Aprobar orden
        </button>
        <button type="submit" name="decision" value="rechazar" class="btn btn--danger">
            Rechazar
        </button>
    </div>
</form>
