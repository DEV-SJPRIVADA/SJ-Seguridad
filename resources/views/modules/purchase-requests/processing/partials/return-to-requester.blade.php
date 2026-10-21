{{-- Devolver al solicitante: solo en Por comprar y sin órdenes. Queda Devuelta y al reenviarse vuelve a autorizarse. --}}
@if ($canReturn)
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-undo-2 width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Devolver al solicitante</h3>
                <p class="pur-req-form__section-desc">{{ $returnHint }} El motivo queda en el hilo y se envía al solicitante.</p>
            </div>
        </header>

        <form method="POST" action="{{ $returnUrl }}" class="form-stack">
            @csrf
            <div class="form-field">
                <label class="form-label" for="motivo_devolucion">Motivo de la devolución</label>
                <textarea
                    id="motivo_devolucion"
                    name="motivo_devolucion"
                    class="form-textarea"
                    rows="3"
                    maxlength="5000"
                    required
                    placeholder="Indica qué debe corregir el solicitante…"
                >{{ old('motivo_devolucion') }}</textarea>
                <x-input-error :messages="$errors->get('motivo_devolucion')" />
            </div>
            <button type="submit" class="btn btn--secondary btn--sm">Devolver al solicitante</button>
        </form>
    </section>
@endif
