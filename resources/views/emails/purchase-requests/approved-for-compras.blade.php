@component('mail::message')
# Solicitud aprobada para Compras

La solicitud **N.º {{ $purchaseRequest->folioVisible() }}** fue aprobada y está disponible en Pedidos.

**Solicitante:** {{ $purchaseRequest->user?->name }}  
**Area:** {{ $purchaseRequest->areaLabel() }}

Gracias,<br>
{{ config('app.name') }}
@endcomponent
