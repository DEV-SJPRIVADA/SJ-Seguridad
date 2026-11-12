{{-- Resumen de la solicitud y de la autorización del grupo (plataforma). --}}
@php
    $purchaseRequest = $batch->purchaseRequest();
    $selected = $batch->seleccionada();
@endphp

<section class="pur-review-panel">
    <header class="pur-review-panel__head">
        <div>
            <h3 class="pur-review-panel__title">Resumen del grupo</h3>
            <p class="pur-review-panel__sub">Datos de la solicitud y de la autorización en curso.</p>
        </div>
    </header>

    <dl class="pur-review-facts">
        <div class="pur-review-facts__item">
            <dt>Solicitud</dt>
            <dd>
                N.º {{ $purchaseRequest?->folioVisible() ?? '—' }}
                <span class="pur-review-facts__hint">{{ $purchaseRequest?->areaLabel() ?? '—' }}</span>
            </dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Solicitante</dt>
            <dd>{{ $purchaseRequest?->user?->name ?? '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Solicitada por</dt>
            <dd>{{ $batch->solicitadaPor()?->name ?? '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Aprobador</dt>
            <dd>{{ $batch->aprobador()?->name ?? '—' }}</dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>Fecha de solicitud</dt>
            <dd>
                @if ($batch->solicitadaAt())
                    {{ $batch->solicitadaAt()->format('d/m/Y H:i') }}
                @else
                    —
                @endif
            </dd>
        </div>
        <div class="pur-review-facts__item">
            <dt>{{ $batch->resueltaAt() ? 'Fecha de decisión' : 'Estado' }}</dt>
            <dd>
                @if ($batch->resueltaAt())
                    {{ $batch->resueltaAt()->format('d/m/Y H:i') }}
                @else
                    {{ $batch->estadoLabel() }}
                @endif
            </dd>
        </div>
    </dl>

    @if ($selected)
        <div class="pur-review-facts__winner">
            <span class="pur-review-facts__winner-icon" aria-hidden="true">
                <x-lucide-badge-check width="18" height="18" />
            </span>
            <div class="pur-review-facts__winner-body">
                <p class="pur-review-facts__winner-label">Cotización ganadora</p>
                <p class="pur-review-facts__winner-name">{{ $selected->descripcionVisible() }}</p>
                <p class="pur-review-facts__winner-meta">
                    {{ $selected->original_name }}
                    @if ($selected->aprobador)
                        · aprobada por {{ $selected->aprobador->name }}
                    @endif
                </p>
            </div>
            @if ($selected->purchaseOrder)
                <span class="status-pill status-pill--success">OC N.º {{ $selected->purchaseOrder->numeroOc }}</span>
            @endif
        </div>
    @endif
</section>
