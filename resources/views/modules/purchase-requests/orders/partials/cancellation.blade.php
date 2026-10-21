{{-- Anulación de la OC: estado de la solicitud, formulario para pedirla (eligiendo director) y opción de retirarla. --}}
@if ($order->isAnulada())
    <div class="alert alert--danger" role="status">
        <strong>Orden anulada.</strong>
        Aprobó {{ $order->anulacionAprobador?->name ?? 'el director' }}
        @if ($order->anulacion_resuelta_at)
            el {{ $order->anulacion_resuelta_at->format('d/m/Y H:i') }}
        @endif
        . Motivo: {{ $order->anulacion_motivo }}
        @if ($order->anulacion_respuesta)
            · Comentario del director: {{ $order->anulacion_respuesta }}
        @endif
    </div>
@elseif ($order->tieneAnulacionPendiente())
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-hourglass width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Anulación pendiente</h3>
                <p class="pur-req-form__section-desc">
                    Esperando la decisión de <strong>{{ $order->anulacionAprobador?->name ?? 'el director' }}</strong>.
                    Pedida por {{ $order->anulacionSolicitadaPor?->name ?? '—' }}
                    @if ($order->anulacion_solicitada_at)
                        el {{ $order->anulacion_solicitada_at->format('d/m/Y H:i') }}
                    @endif
                    . Motivo: {{ $order->anulacion_motivo }}
                </p>
            </div>
        </header>

        @if ($canWithdrawCancellation)
            <form method="POST" action="{{ route('purchase-requests.orders.cancellation.withdraw', ['module' => $module, 'purchase_order' => $order->getKey()]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--secondary btn--sm">Retirar solicitud de anulación</button>
            </form>
        @endif
    </section>
@elseif ($canRequestCancellation)
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-ban width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Solicitar anulación</h3>
                <p class="pur-req-form__section-desc">
                    Úsalo si la orden quedó mal emitida. El director que elijas debe aprobarla. Mientras tanto la orden no se puede evaluar.
                    @if ($order->anulacion_respuesta && $order->anulacion_resuelta_at)
                        Última solicitud rechazada: {{ $order->anulacion_respuesta }}
                    @endif
                </p>
            </div>
        </header>

        <form method="POST" action="{{ route('purchase-requests.orders.cancellation.request', ['module' => $module, 'purchase_order' => $order->getKey()]) }}" class="form-stack">
            @csrf
            <div class="form-field">
                <label class="form-label" for="anulacion_aprobador_id">Director que aprueba</label>
                <x-searchable-select
                    id="anulacion_aprobador_id"
                    name="anulacion_aprobador_id"
                    :options="$approverOptions"
                    :value="old('anulacion_aprobador_id', '')"
                    placeholder="Seleccione un director"
                    searchPlaceholder="Buscar director…"
                    required
                />
                <x-input-error :messages="$errors->get('anulacion_aprobador_id')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="motivo_anulacion">Motivo de la anulación</label>
                <textarea
                    id="motivo_anulacion"
                    name="motivo_anulacion"
                    class="form-textarea"
                    rows="3"
                    maxlength="5000"
                    required
                    placeholder="Indica por qué se debe anular la orden…"
                >{{ old('motivo_anulacion') }}</textarea>
                <x-input-error :messages="$errors->get('motivo_anulacion')" />
            </div>
            <button type="submit" class="btn btn--secondary btn--sm">Solicitar anulación</button>
        </form>
    </section>
@endif
