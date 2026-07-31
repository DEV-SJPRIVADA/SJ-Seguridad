@component('mail::message')
# Solicitud aprobada para Compras

La solicitud **N.º {{ $purchaseRequest->folio() }}** fue aprobada y esta disponible en la bandeja de Compras.

**Solicitante:** {{ $purchaseRequest->user?->name }}  
**Area:** {{ $purchaseRequest->areaLabel() }}

Gracias,<br>
{{ config('app.name') }}
@endcomponent
