{{-- Formulario de decisión del director: aprobar (comentario opcional) o rechazar (comentario obligatorio). --}}
<form method="POST" action="{{ $decideUrl }}" class="req-approval-letter__form">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="form-field">
        <x-input-label for="comentario_anulacion" value="Comentario (obligatorio si rechaza)" />
        <textarea
            id="comentario_anulacion"
            name="comentario_anulacion"
            class="form-textarea"
            rows="3"
            maxlength="5000"
            placeholder="Obligatorio si rechaza. Opcional al aprobar."
        >{{ old('comentario_anulacion') }}</textarea>
        <x-input-error :messages="$errors->get('comentario_anulacion')" />
        <x-input-error :messages="$errors->get('decision')" />
    </div>

    <div class="req-approval-letter__actions">
        <button type="submit" name="decision" value="aprobar" class="btn btn--danger">
            Aprobar anulación
        </button>
        <button type="submit" name="decision" value="rechazar" class="btn btn--secondary">
            Rechazar
        </button>
    </div>
</form>
