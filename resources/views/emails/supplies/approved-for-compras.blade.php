@component('mail::message')
# Suministro aprobado por Calidad

El pedido de insumos **#{{ $supplyRequest->id }}** fue aprobado por Calidad y esta disponible en la bandeja de Compras.

**Solicitante:** {{ $supplyRequest->user?->name }}  
**Area:** {{ config('access.areas.'.$supplyRequest->area_key, $supplyRequest->area_key) }}

Gracias,<br>
{{ config('app.name') }}
@endcomponent
