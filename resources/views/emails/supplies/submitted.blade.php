<x-mail::message>
# Nueva solicitud de suministros

Se registro una nueva solicitud de suministros en **SJ Seguridad**. A continuacion, los detalles principales:

<x-mail::panel>
**Solicitud:** #{{ $supplyRequest->id }}  
**Solicitante:** {{ $supplyRequest->user?->name }}  
**Area:** {{ config('access.areas.' . $supplyRequest->area_key) ?? $supplyRequest->area_key }}  
**Productos:** {{ $supplyRequest->items->count() }}  
**Fecha:** {{ $supplyRequest->created_at?->format('d/m/Y H:i') }}
</x-mail::panel>

<x-mail::button :url="route('supplies.quality.edit', ['module' => $supplyRequest->area_key, 'supply_request' => $supplyRequest->id])">
Revisar solicitud
</x-mail::button>

Ingresa al tablero de Revision Calidad para aprobar o ajustar cantidades.

Atentamente,  
Sistema de Notificaciones **{{ config('app.name') }}**
</x-mail::message>
