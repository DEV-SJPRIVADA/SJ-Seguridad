<x-mail::message>
# Actualización de su Requisición

El estado de su solicitud de personal en **{{ config('app.name') }}** ha cambiado.

<x-mail::panel>
**Código:** {{ $requisition->code }}  
**Cargo:** {{ $requisition->position?->name }}  
**Cliente:** {{ $requisition->client?->name }}  
**Estado anterior:** {{ $fromStatusLabel }}  
**Estado nuevo:** {{ $toStatusLabel }}  
@if ($requisition->human_resources_observation)
**Observación GH:** {{ $requisition->human_resources_observation }}  
@endif
</x-mail::panel>

<x-mail::button :url="route('requisitions.tracking', ['module' => $requisition->requesting_area_key, 'q' => $requisition->code])">
Ver en Seguimiento
</x-mail::button>

Puede consultar el detalle y el historial desde el tablero de Seguimiento de su área.

Atentamente,  
Sistema de Notificaciones **{{ config('app.name') }}**
</x-mail::message>
