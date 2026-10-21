@component('mail::message')
# Suministro aprobado por Calidad

El pedido de insumos **{{ $supplyRequest->folioVisible() }}** fue aprobado por Calidad y está disponible en Pedidos.

**Solicitante:** {{ $supplyRequest->user?->name }}  
**Área:** {{ config('access.areas.'.$supplyRequest->area_key, $supplyRequest->area_key) }}

Gracias,<br>
{{ config('app.name') }}
@endcomponent
