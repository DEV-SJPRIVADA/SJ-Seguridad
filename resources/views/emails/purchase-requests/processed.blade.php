@component('mail::message')
# Solicitud procesada por Compras

La solicitud **N.º {{ $purchaseRequest->folio() }}** fue actualizada a **{{ $purchaseRequest->estadoComprasLabel() }}**.

@if ($purchaseRequest->comentarios_compras)
**Comentarios Compras:** {{ $purchaseRequest->comentarios_compras }}
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
