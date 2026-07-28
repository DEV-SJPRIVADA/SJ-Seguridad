<x-mail::message>
# Autorizacion de requisicion (cargo nuevo)

Se registro una solicitud de personal que **requiere autorizacion de gerencia** antes de que Gestion humana continúe el proceso.

<x-mail::panel>
**Codigo:** {{ $requisition->code }}  
**Motivo:** {{ $requisition->requestReason?->name ?? '—' }}  
**Cargo:** {{ $requisition->position?->name ?? '—' }}  
**Solicitante:** {{ $requisition->requester?->name ?? '—' }}  
**Area solicitante:** {{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}  
@if ($totalQuantity > 1)
**Vacantes en este lote:** {{ $totalQuantity }}  
@endif
</x-mail::panel>

<x-mail::button :url="route('requisitions.management-approval.show', ['module' => 'gestion_humana', 'requisition' => $requisition->id])">
Ingresar y revisar
</x-mail::button>

Tambien puede autorizar desde **Requisiciones → Autorizacion gerencia** en el tablero de Gestion humana.

Atentamente,  
**{{ config('app.name') }}**
</x-mail::message>
