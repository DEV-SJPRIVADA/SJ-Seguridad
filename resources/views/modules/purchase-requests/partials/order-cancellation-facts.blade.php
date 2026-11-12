{{-- Resumen de la orden y de la solicitud de anulación (plataforma). --}}
<section class="pur-review-panel">
    <header class="pur-review-panel__head">
        <div>
            <h3 class="pur-review-panel__title">Resumen de la orden</h3>
            <p class="pur-review-panel__sub">Datos de la orden que Compras pidió anular.</p>
        </div>
    </header>

    <dl class="pur-review-facts">
        <div class="pur-review-facts__item">
            <dt>Origen</dt>
            <dd>
                {{ $order->sourceLabel() }}
                <span class="pur-review-facts__hint">N.º {{ $order->sourceFolio() }}</span>
            </dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Proveedor</dt>
            <dd>
                {{ $order->provider_nombre }}
                <span class="pur-review-facts__hint">{{ $order->provider_identificacion ?: 'Sin identificación' }}</span>
            </dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Fecha de la orden</dt>
            <dd>{{ $order->fechaImpresa() ?: '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Total de la orden</dt>
            <dd>{{ number_format((float) $order->total, 2) }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Solicitada por</dt>
            <dd>{{ $order->anulacionSolicitadaPor?->name ?? '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Director que decide</dt>
            <dd>{{ $order->anulacionAprobador?->name ?? '—' }}</dd>
        </div>
    </dl>

    @if (filled($order->anulacion_motivo))
        <div class="pur-ord-note pur-ord-note--danger">
            <p class="pur-ord-note__label">
                <x-lucide-message-square-text width="15" height="15" aria-hidden="true" />
                Motivo de la anulación
            </p>
            <p class="pur-ord-note__text">{{ $order->anulacion_motivo }}</p>
        </div>
    @endif
</section>
