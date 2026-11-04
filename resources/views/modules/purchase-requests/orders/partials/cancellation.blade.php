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
@endif
