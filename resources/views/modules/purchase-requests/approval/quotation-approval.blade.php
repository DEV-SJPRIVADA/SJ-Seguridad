<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $purchaseRequest = $batch->purchaseRequest();
        $aprobador = $batch->aprobador();
    @endphp

    {{-- Revisión de un grupo de cotizaciones por el director elegido (plataforma). --}}
    <div class="page-section purchase-requests-page purchase-requests-page--detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a Cotizaciones"
                        aria-label="Volver a Cotizaciones"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>

                <h2 class="page-title">Aprobación de cotizaciones</h2>
                <p class="page-subtitle">
                    Grupo <strong>{{ $batch->label }}</strong>
                    @if ($purchaseRequest)
                        · Solicitud N.º {{ $purchaseRequest->folioVisible() }} · {{ $purchaseRequest->areaLabel() }}
                    @endif
                </p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill {{ $batch->estadoPillClass() }}">{{ $batch->estadoLabel() }}</span>
                    <span class="status-pill status-pill--info">{{ $batch->count() }} cotización(es)</span>
                    @if ($aprobador)
                        <span class="status-pill status-pill--muted">Aprobador: {{ $aprobador->name }}</span>
                    @endif
                </div>
            </div>

            @if ($batch->estaPendiente())
                <div class="alert alert--warning pur-review-page__notice" role="status">
                    <p><strong>Este grupo espera su decisión.</strong> Revise los documentos y elija la cotización ganadora.
                        Mientras no se resuelva, Compras no puede emitir la orden de compra de este grupo.</p>
                </div>
            @else
                <div class="alert alert--info pur-review-page__notice" role="status">
                    <p>Este grupo ya fue gestionado. Estado: <strong>{{ $batch->estadoLabel() }}</strong>.
                        Puede consultar los documentos y el resultado sin modificar la decisión.</p>
                </div>
            @endif

            <div class="pur-review-page__layout">
                <div class="pur-review-page__main">
                    @include('modules.purchase-requests.partials.quotation-approval-facts', ['batch' => $batch])
                    @include('modules.purchase-requests.partials.quotation-approval-documents', ['batch' => $batch, 'fileUrls' => $fileUrls])
                </div>

                <aside class="pur-review-page__aside">
                    @if ($batch->estaPendiente())
                        @include('modules.purchase-requests.partials.quotation-approval-decision', [
                            'batch' => $batch,
                            'decideUrl' => $decideUrl,
                            'method' => 'PATCH',
                        ])
                    @else
                        @include('modules.purchase-requests.partials.quotation-approval-outcome', ['batch' => $batch])
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
