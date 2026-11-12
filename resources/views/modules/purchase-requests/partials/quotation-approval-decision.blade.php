{{-- Decisión del aprobador sobre el grupo: elige la cotización ganadora y aprueba, o rechaza todo. --}}
<form
    method="POST"
    action="{{ $decideUrl }}"
    class="pur-review-decision"
    x-data="{ winner: @js((string) old('purchase_quotation_id', '')) }"
>
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <header class="pur-review-decision__head">
        <p class="pur-review-decision__eyebrow">Su decisión</p>
        <h3 class="pur-review-decision__title">Aprobar o rechazar el grupo</h3>
        <p class="pur-review-decision__lead">
            Elija la cotización ganadora y apruebe el grupo, o rechace el grupo completo con un motivo.
        </p>
    </header>

    <fieldset class="pur-review-decision__group">
        <legend class="pur-review-decision__legend">
            Cotización ganadora
            <span class="pur-review-decision__label-hint">obligatoria si aprueba</span>
        </legend>

        <div class="pur-review-winner-list" role="radiogroup" aria-label="Cotización ganadora">
            @foreach ($batch->quotations as $quotation)
                <label
                    class="pur-review-winner"
                    x-bind:class="{ 'pur-review-winner--selected': winner === '{{ $quotation->id }}' }"
                >
                    <input
                        type="radio"
                        name="purchase_quotation_id"
                        value="{{ $quotation->id }}"
                        class="pur-review-winner__input"
                        x-model="winner"
                        @checked((string) old('purchase_quotation_id') === (string) $quotation->id)
                    >
                    <span class="pur-review-winner__ordinal" aria-hidden="true">{{ $loop->iteration }}</span>
                    <span class="pur-review-winner__body">
                        <span class="pur-review-winner__name">{{ $quotation->descripcionVisible() }}</span>
                        <span class="pur-review-winner__meta">{{ $quotation->original_name }} · {{ $quotation->sizeLabel() }}</span>
                    </span>
                    <span class="pur-review-winner__mark" aria-hidden="true">
                        <x-lucide-check width="13" height="13" />
                    </span>
                </label>
            @endforeach
        </div>

        <x-input-error :messages="$errors->get('purchase_quotation_id')" />
    </fieldset>

    <div class="pur-review-decision__field">
        <label class="pur-review-decision__label" for="quotation_comentario">
            Comentario
            <span class="pur-review-decision__label-hint">obligatorio si rechaza</span>
        </label>
        <textarea
            id="quotation_comentario"
            name="comentario"
            class="form-textarea pur-review-decision__textarea"
            rows="3"
            maxlength="5000"
            placeholder="Escriba el motivo del rechazo o una observación para Compras."
        >{{ old('comentario') }}</textarea>
        <x-input-error :messages="$errors->get('comentario')" />
        <x-input-error :messages="$errors->get('decision')" />
    </div>

    <div class="pur-review-decision__actions">
        <button type="submit" name="decision" value="aprobar" class="btn btn--primary">
            <x-lucide-badge-check width="16" height="16" aria-hidden="true" />
            Aprobar grupo
        </button>
        <button type="submit" name="decision" value="rechazar" class="btn btn--secondary pur-review-decision__reject">
            <x-lucide-x width="16" height="16" aria-hidden="true" />
            Rechazar grupo
        </button>
    </div>

    <p class="pur-review-decision__hint">
        Al aprobar, la cotización marcada queda como <strong>ganadora</strong> y las demás como no seleccionadas.
        Si rechaza, Compras recibirá el motivo y podrá cargar otra cotización en el grupo o volver a solicitarlo.
    </p>
</form>
