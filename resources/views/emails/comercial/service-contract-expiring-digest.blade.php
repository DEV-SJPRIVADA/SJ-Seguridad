<x-mail::message>
# Contratos de servicio comercial — por vencer

Servicios activos con contrato **por vencer** (ventana de 30 dias; fecha de referencia: **{{ \App\Support\DisplayDate::date($asOf) }}**).

@foreach ($services as $service)
<x-mail::panel>
**NIT:** {{ $service['nit'] }}  
**Cliente:** {{ $service['client_name'] }}  
**Contrato:** {{ $service['contract_number'] ?? '—' }}  
**Fin de contrato:** {{ \App\Support\DisplayDate::date($service['contract_end']) }} ({{ $service['days_remaining'] }} dias restantes)

<x-mail::button :url="$service['edit_url']">
Editar servicio
</x-mail::button>
</x-mail::panel>
@endforeach

<x-mail::button :url="$servicesIndexUrl">
Ver servicios por vencer
</x-mail::button>

Atentamente,  
Sistema de notificaciones **{{ config('app.name') }}**
</x-mail::message>
