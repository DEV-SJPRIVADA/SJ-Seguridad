{{-- Resultado del grupo ya resuelto: estado, ganadora, comentario y vínculo con la OC (plataforma). --}}
@php
    $selected = $batch->seleccionada();
    $resolvedOrder = $selected?->purchaseOrder;
@endphp

<section class="pur-review-outcome">
    <header class="pur-review-outcome__head">
        <span class="pur-review-outcome__icon" aria-hidden="true">
            @if ($batch->estaAprobada())
                <x-lucide-circle-check width="20" height="20" />
            @else
                <x-lucide-triangle-alert width="20" height="20" />
            @endif
        </span>
        <div>
            <p class="pur-review-outcome__eyebrow">Resultado</p>
            <h3 class="pur-review-outcome__title">{{ $batch->estadoLabel() }}</h3>
        </div>
    </header>

    @if ($batch->estaAprobada() && $selected)
        <div class="pur-review-outcome__winner">
            <p class="pur-review-outcome__label">Cotización ganadora</p>
            <p class="pur-review-outcome__name">{{ $selected->descripcionVisible() }}</p>
            <p class="pur-review-outcome__meta">{{ $selected->original_name }} · {{ $selected->sizeLabel() }}</p>
        </div>
    @else
        <p class="pur-review-outcome__message">
            El director rechazó el grupo completo. Compras puede cargar otras cotizaciones y solicitar una nueva aprobación.
        </p>
    @endif

    @if (filled($batch->comentario()))
        <div class="pur-review-outcome__comment">
            <p class="pur-review-outcome__label">Comentario del aprobador</p>
            <p class="pur-review-outcome__comment-text">{{ $batch->comentario() }}</p>
        </div>
    @endif

    <dl class="pur-review-outcome__dates">
        <div>
            <dt>Solicitada</dt>
            <dd>{{ $batch->solicitadaAt()?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Resuelta</dt>
            <dd>{{ $batch->resueltaAt()?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Aprobador</dt>
            <dd>{{ $batch->aprobador()?->name ?? '—' }}</dd>
        </div>
    </dl>

    @if ($resolvedOrder)
        <a
            href="{{ route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $resolvedOrder->getKey()]) }}"
            class="pur-review-outcome__link"
        >
            <x-lucide-file-text width="15" height="15" aria-hidden="true" />
            Ver OC N.º {{ $resolvedOrder->numeroOc }}
        </a>
    @endif
</section>
