{{-- Aprobación opcional de la OC: pedirla a un director o administrador, ver el estado y retirarla. --}}
@if ($order->isAprobada())
    <div class="alert alert--success" role="status">
        <strong>Orden aprobada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}
        @if ($order->aprobacion_resuelta_at)
            el {{ $order->aprobacion_resuelta_at->format('d/m/Y H:i') }}
        @endif
        @if ($order->aprobacion_comentario)
            · Comentario: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->aprobacion_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA)
    <div class="alert alert--danger" role="status">
        <strong>Aprobación rechazada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}; la orden quedó anulada.
        @if ($order->aprobacion_comentario)
            Motivo: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->tieneAprobacionPendiente())
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-hourglass width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Aprobación pendiente</h3>
                <p class="pur-req-form__section-desc">
                    Esperando la decisión de <strong>{{ $order->aprobacionAprobador?->name ?? '—' }}</strong>.
                    Pedida por {{ $order->aprobacionSolicitadaPor?->name ?? '—' }}
                    @if ($order->aprobacion_solicitada_at)
                        el {{ $order->aprobacion_solicitada_at->format('d/m/Y H:i') }}
                    @endif
                    @if ($order->aprobacion_nota)
                        · Nota: {{ $order->aprobacion_nota }}
                    @endif
                </p>
            </div>
        </header>

        @if ($canWithdrawApproval)
            <form method="POST" action="{{ route('purchase-requests.orders.approval.withdraw', ['module' => $module, 'purchase_order' => $order->getKey()]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--secondary btn--sm">Retirar solicitud de aprobación</button>
            </form>
        @endif
    </section>
@endif
