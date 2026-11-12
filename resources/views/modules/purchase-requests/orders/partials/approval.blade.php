{{-- Autorización obligatoria de la OC: estado de la firma del director administrativo. --}}
@if ($order->isAprobada())
    <div class="alert alert--success" role="status">
        <strong>Orden autorizada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}
        @if ($order->aprobacion_resuelta_at)
            el {{ $order->aprobacion_resuelta_at->format('d/m/Y H:i') }}
        @endif
        @if ($order->aprobacion_comentario)
            · Comentario: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->aprobacion_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA)
    <div class="alert alert--danger" role="status">
        <strong>Autorización rechazada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}; la orden quedó anulada.
        @if ($order->aprobacion_comentario)
            Motivo: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->estado === \App\Models\PurchaseOrder::ESTADO_PENDIENTE_APROBACION)
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-hourglass width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Pendiente de autorización</h3>
                <p class="pur-req-form__section-desc">
                    @if ($order->aprobacionAprobador)
                        Esperando la decisión de <strong>{{ $order->aprobacionAprobador->name }}</strong>.
                    @else
                        Falta asignar al director administrativo que autoriza esta orden.
                    @endif
                    Enviada por {{ $order->aprobacionSolicitadaPor?->name ?? '—' }}
                    @if ($order->aprobacion_solicitada_at)
                        el {{ $order->aprobacion_solicitada_at->format('d/m/Y H:i') }}
                    @endif
                    @if ($order->aprobacion_nota)
                        · Nota: {{ $order->aprobacion_nota }}
                    @endif
                </p>
                <p class="pur-req-form__section-desc">
                    Mientras no esté autorizada, la orden reserva el saldo del pedido pero no se puede
                    imprimir, enviar al proveedor ni evaluar. Si ya no se necesita, rechácela.
                </p>
            </div>
        </header>
    </section>
@endif
