{{-- Resumen de la orden y de la solicitud de aprobación (plataforma). --}}
<section class="pur-review-panel">
    <header class="pur-review-panel__head">
        <div>
            <h3 class="pur-review-panel__title">Resumen de la orden</h3>
            <p class="pur-review-panel__sub">Datos de la orden que Compras envió a su autorización.</p>
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
            <dd>{{ $order->aprobacionSolicitadaPor?->name ?? '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Aprobador</dt>
            <dd>{{ $order->aprobacionAprobador?->name ?? '—' }}</dd>
        </div>
        {{-- Cotización aprobada que Compras vinculó al emitir la OC (opcional). --}}
        @if ($order->purchaseQuotation)
            <div class="pur-review-facts__item">
                <dt>Cotización aprobada</dt>
                <dd>
                    {{ $order->purchaseQuotation->descripcionVisible() }}
                    <span class="pur-review-facts__hint">{{ $order->purchaseQuotation->loteLabel() }}</span>
                </dd>
            </div>
            <div class="pur-review-facts__item">
                <dt>Aprobada por</dt>
                <dd>
                    {{ $order->purchaseQuotation->aprobador?->name ?? '—' }}
                    @if ($order->purchaseQuotation->resuelta_at)
                        <span class="pur-review-facts__hint">
                            <x-date-table :value="$order->purchaseQuotation->resuelta_at" datetime />
                        </span>
                    @endif
                </dd>
            </div>
        @endif
    </dl>

    @if (filled($order->aprobacion_nota))
        <div class="pur-ord-note">
            <p class="pur-ord-note__label">
                <x-lucide-message-square-text width="15" height="15" aria-hidden="true" />
                Nota de Compras
            </p>
            <p class="pur-ord-note__text">{{ $order->aprobacion_nota }}</p>
        </div>
    @endif
</section>
