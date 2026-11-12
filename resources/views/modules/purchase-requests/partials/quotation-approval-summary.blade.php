{{-- Resumen del grupo de cotizaciones y sus documentos (plataforma y enlace del correo). --}}
@php
    $purchaseRequest = $batch->purchaseRequest();
@endphp

<div class="req-approval-letter__panel">
    <p><strong>Solicitud:</strong> N.º {{ $purchaseRequest?->folioVisible() ?? '—' }} · {{ $purchaseRequest?->areaLabel() ?? '—' }}</p>
    <p><strong>Solicitante:</strong> {{ $purchaseRequest?->user?->name ?? '—' }}</p>
    <p><strong>Grupo / OC futura:</strong> {{ $batch->label }}</p>
    <p><strong>Cotizaciones en el grupo:</strong> {{ $batch->count() }}</p>
    <p><strong>Estado:</strong> {{ $batch->estadoLabel() }}</p>
    <p><strong>Solicitada por:</strong> {{ $batch->solicitadaPor()?->name ?? '—' }}</p>
    <p><strong>Aprobador:</strong> {{ $batch->aprobador()?->name ?? '—' }}</p>
    @if ($batch->solicitadaAt())
        <p><strong>Fecha de solicitud:</strong> {{ $batch->solicitadaAt()->format('d/m/Y H:i') }}</p>
    @endif
    @if ($batch->resueltaAt())
        <p><strong>Fecha de decisión:</strong> {{ $batch->resueltaAt()->format('d/m/Y H:i') }}</p>
    @endif
    @if (filled($batch->comentario()))
        <p><strong>Comentario del aprobador:</strong> {{ $batch->comentario() }}</p>
    @endif
    @if ($batch->seleccionada()?->purchaseOrder)
        <p><strong>Vinculada a:</strong> OC N.º {{ $batch->seleccionada()->purchaseOrder->numeroOc }}</p>
    @endif
</div>

@foreach ($batch->quotations as $quotation)
    <div class="req-approval-letter__panel">
        <p>
            <strong>Cotización {{ $loop->iteration }}:</strong> {{ $quotation->descripcionVisible() }}
            <span class="status-pill {{ $quotation->estadoPillClass() }}">{{ $quotation->estadoLabel() }}</span>
        </p>
        <p><strong>Archivo:</strong> {{ $quotation->original_name }} ({{ $quotation->sizeLabel() }})</p>
        <p><strong>Cargada por:</strong> {{ $quotation->uploadedBy?->name ?? '—' }}</p>

        @if ($quotation->esPdf())
            <iframe
                src="{{ $fileUrls[$quotation->id] ?? '' }}"
                title="Cotización {{ $quotation->descripcionVisible() }}"
                style="width: 100%; height: 60vh; min-height: 380px; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #f8fafc;"
            ></iframe>
        @elseif ($quotation->esImagen())
            <img
                src="{{ $fileUrls[$quotation->id] ?? '' }}"
                alt="Cotización {{ $quotation->descripcionVisible() }}"
                style="max-width: 100%; height: auto; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #f8fafc;"
            >
        @else
            <p class="text-muted">Este archivo no se puede previsualizar. Ábralo con el siguiente enlace.</p>
        @endif

        <p style="margin-top: 0.75rem;">
            <a href="{{ $fileUrls[$quotation->id] ?? '' }}" target="_blank" rel="noopener">Abrir archivo en otra pestaña</a>
        </p>
    </div>
@endforeach
