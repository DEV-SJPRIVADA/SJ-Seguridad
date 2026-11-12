{{-- Resultado de una orden ya resuelta: decisión, comentario y fechas (plataforma). --}}
@php
    $approved = $order->isAprobada();
@endphp

<section class="pur-review-outcome">
    <header class="pur-review-outcome__head">
        <span class="pur-review-outcome__icon" aria-hidden="true">
            @if ($approved)
                <x-lucide-circle-check width="20" height="20" />
            @else
                <x-lucide-badge-x width="20" height="20" />
            @endif
        </span>
        <div>
            <p class="pur-review-outcome__eyebrow">Resultado</p>
            <h3 class="pur-review-outcome__title">{{ $order->aprobacionLabel() }}</h3>
        </div>
    </header>

    <p class="pur-review-outcome__message">
        @if ($approved)
            La orden N.º {{ $order->numeroOc }} quedó aprobada por
            <strong>{{ $order->aprobacionAprobador?->name ?? 'el aprobador' }}</strong>.
            Compras ya cuenta con esta autorización.
        @else
            La orden N.º {{ $order->numeroOc }} fue rechazada y quedó
            <strong>{{ $order->estadoLabel() }}</strong>; su cantidad volvió al saldo del pedido.
        @endif
    </p>

    @if (filled($order->aprobacion_comentario))
        <div class="pur-review-outcome__comment">
            <p class="pur-review-outcome__label">Comentario del aprobador</p>
            <p class="pur-review-outcome__comment-text">{{ $order->aprobacion_comentario }}</p>
        </div>
    @endif

    <dl class="pur-review-outcome__dates">
        <div>
            <dt>Solicitada</dt>
            <dd>{{ $order->aprobacion_solicitada_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Resuelta</dt>
            <dd>{{ $order->aprobacion_resuelta_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Aprobador</dt>
            <dd>{{ $order->aprobacionAprobador?->name ?? '—' }}</dd>
        </div>
    </dl>
</section>
