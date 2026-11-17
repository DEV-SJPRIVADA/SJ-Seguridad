{{-- Autorización obligatoria de la OC: paso 1 (quien autorizó la solicitud) y paso 2 (Director Admon.). --}}
@if ($order->isAprobada())
    <div class="alert alert--success" role="status">
        <strong>Orden autorizada</strong>
        @if ($order->tieneAprobacionDosPasos())
            · Área: {{ $order->aprobacionAreaAprobador?->name ?? '—' }}
        @endif
        · Director Admon.: {{ $order->aprobacionAprobador?->name ?? '—' }}
        @if ($order->aprobacion_resuelta_at)
            el {{ $order->aprobacion_resuelta_at->format('d/m/Y H:i') }}
        @endif
        @if ($order->aprobacion_comentario)
            · Comentario: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->aprobacion_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA || $order->aprobacion_area_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA)
    <div class="alert alert--danger" role="status">
        <strong>Autorización rechazada</strong>
        por {{ $order->aprobacion_area_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA
            ? ($order->aprobacionAreaAprobador?->name ?? 'el área')
            : ($order->aprobacionAprobador?->name ?? 'el Director Admon.') }};
        la orden quedó anulada.
        @if ($order->aprobacion_area_comentario || $order->aprobacion_comentario)
            Motivo: {{ $order->aprobacion_area_comentario ?: $order->aprobacion_comentario }}
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
                    @if ($order->pasoAreaPendiente())
                        Paso 1: esperando el visto bueno de <strong>{{ $order->aprobacionAreaAprobador?->name ?? 'quien autorizó la solicitud' }}</strong>.
                        @if ($order->aprobacionAprobador)
                            Paso 2: <strong>{{ $order->aprobacionAprobador->name }}</strong> (Director Admon.) decide después.
                        @endif
                    @elseif ($order->aprobacionAprobador)
                        Paso 1 listo. Esperando la autorización de <strong>{{ $order->aprobacionAprobador->name }}</strong>.
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
                    imprimir, enviar al proveedor ni evaluar.
                </p>
            </div>
        </header>
    </section>
@endif
