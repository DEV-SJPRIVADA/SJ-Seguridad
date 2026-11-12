{{-- Resultado de una anulación ya resuelta: decisión, motivo, respuesta y fechas (plataforma). --}}
@php
    $anulacionAprobada = $order->anulacion_resultado === \App\Models\PurchaseOrder::APROBACION_APROBADA;
@endphp

<section class="pur-review-outcome">
    <header class="pur-review-outcome__head">
        <span class="pur-review-outcome__icon" aria-hidden="true">
            @if ($anulacionAprobada)
                <x-lucide-ban width="20" height="20" />
            @else
                <x-lucide-circle-check width="20" height="20" />
            @endif
        </span>
        <div>
            <p class="pur-review-outcome__eyebrow">Resultado</p>
            <h3 class="pur-review-outcome__title">{{ $order->anulacionResultadoLabel() }}</h3>
        </div>
    </header>

    <p class="pur-review-outcome__message">
        @if ($anulacionAprobada)
            La orden N.º {{ $order->numeroOc }} quedó <strong>{{ $order->estadoLabel() }}</strong>
            y su cantidad volvió al saldo del pedido.
        @else
            La anulación fue rechazada: la orden N.º {{ $order->numeroOc }} sigue
            <strong>{{ $order->estadoLabel() }}</strong>.
        @endif
    </p>

    @if (filled($order->anulacion_motivo))
        <div class="pur-review-outcome__comment">
            <p class="pur-review-outcome__label">Motivo de Compras</p>
            <p class="pur-review-outcome__comment-text">{{ $order->anulacion_motivo }}</p>
        </div>
    @endif

    @if (filled($order->anulacion_respuesta))
        <div class="pur-review-outcome__comment">
            <p class="pur-review-outcome__label">Comentario del director</p>
            <p class="pur-review-outcome__comment-text">{{ $order->anulacion_respuesta }}</p>
        </div>
    @endif

    <dl class="pur-review-outcome__dates">
        <div>
            <dt>Solicitada</dt>
            <dd>{{ $order->anulacion_solicitada_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Resuelta</dt>
            <dd>{{ $order->anulacion_resuelta_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Director</dt>
            <dd>{{ $order->anulacionAprobador?->name ?? '—' }}</dd>
        </div>
    </dl>
</section>
