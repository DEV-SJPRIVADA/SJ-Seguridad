{{-- Cierre manual del saldo: motivo obligatorio en el hilo y paso a Cerrado en la misma acción. --}}
@if ($canClose)
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-lock width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Cerrar saldo</h3>
                <p class="pur-req-form__section-desc">Deja el pedido en Cerrado aunque todavía quede cantidad por ordenar. El motivo queda en el hilo.</p>
            </div>
        </header>

        <form method="POST" action="{{ $closeUrl }}" class="form-stack">
            @csrf
            <div class="form-field">
                <label class="form-label" for="motivo">Motivo</label>
                <textarea
                    id="motivo"
                    name="motivo"
                    class="form-textarea"
                    rows="3"
                    maxlength="5000"
                    required
                    placeholder="Indica por qué se cierra el saldo…"
                >{{ old('motivo') }}</textarea>
                <x-input-error :messages="$errors->get('motivo')" />
            </div>
            <button type="submit" class="btn btn--primary btn--sm">Cerrar saldo</button>
        </form>
    </section>
@endif
