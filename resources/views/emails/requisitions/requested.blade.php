<x-mail::message>
# Nueva Requisición de Personal

Se ha registrado una nueva solicitud de personal en el sistema **SJ Seguridad**. A continuación, los detalles principales:

<x-mail::panel>
**Código:** {{ $requisition->code }} {{ $totalQuantity > 1 ? '(Lote de ' . $totalQuantity . ' registros)' : '' }}  
**Cargo:** {{ $requisition->position?->name }}  
**Cliente:** {{ $requisition->client?->name }}  
**Cantidad Total Solicitada:** {{ $totalQuantity }}  
**Área Solicitante:** {{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}  
**Solicitado por:** {{ $requisition->requester?->name }}  
**Fecha de Solicitud:** {{ $requisition->request_date?->format('d/m/Y') }}
</x-mail::panel>

<x-mail::button :url="route('requisitions.manage', ['module' => 'gestion_humana', 'search' => $requisition->code])">
Ver Requisición
</x-mail::button>

Si deseas gestionar esta solicitud, puedes ingresar al tablero de Gestión Humana.

Atentamente,  
Sistema de Notificaciones **{{ config('app.name') }}**
</x-mail::message>
