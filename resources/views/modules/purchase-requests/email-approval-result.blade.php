<x-guest-layout>
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Decision registrada</h1>

                <div class="alert {{ $estado === \App\Models\PurchaseRequest::ESTADO_APROBADO ? 'alert--success' : 'alert--warning' }} ficha-empleados-page__alert">
                    @if ($estado === \App\Models\PurchaseRequest::ESTADO_APROBADO)
                        La solicitud de compra <strong>N.º {{ $purchaseRequest->folio() }}</strong> fue <strong>aprobada</strong> correctamente.
                    @else
                        La solicitud de compra <strong>N.º {{ $purchaseRequest->folio() }}</strong> fue <strong>rechazada</strong>.
                    @endif
                </div>

                <div class="req-approval-letter__panel">
                    <p><strong>Folio:</strong> {{ $purchaseRequest->folio() }}</p>
                    <p><strong>Solicitante:</strong> {{ $purchaseRequest->user?->name ?? '—' }}</p>
                    <p><strong>Area:</strong> {{ $purchaseRequest->areaLabel() ?? '—' }}</p>
                </div>

                @if ($purchaseRequest->comentarios_director)
                    <div class="req-approval-letter__panel req-approval-letter__panel--muted">
                        <p><strong>Comentarios del director:</strong> {{ $purchaseRequest->comentarios_director }}</p>
                    </div>
                @endif

                <p class="req-approval-letter__hint">
                    El solicitante recibira un correo con el resultado. Puede cerrar esta ventana.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
