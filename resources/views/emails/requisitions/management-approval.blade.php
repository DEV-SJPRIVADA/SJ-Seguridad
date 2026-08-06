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

<x-mail::button :url="$emailApprovalUrl">
Autorizar por correo
</x-mail::button>

Tambien puede ingresar a la plataforma: [Revisar en el tablero]({{ $platformUrl }})

Atentamente,  
**{{ config('app.name') }}**
</x-mail::message>
