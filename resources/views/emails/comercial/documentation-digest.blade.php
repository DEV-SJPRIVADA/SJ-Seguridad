<x-mail::message>
# Documentacion comercial — aviso

Clientes con documentacion **por vencer** o **vencida** segun la matriz checklist (fecha de referencia: **{{ \App\Support\DisplayDate::date($asOf) }}**).

@foreach ($clients as $client)
<x-mail::panel>
**NIT:** {{ $client['nit'] }}  
**Cliente:** {{ $client['name'] }}  
**Vencimiento:** {{ \App\Support\DisplayDate::date($client['documentation_expires_on']) }}  
**Estado:** {{ $client['status_label'] }}@if ($client['days_remaining'] !== null) ({{ $client['days_remaining'] }} dias restantes)@endif
</x-mail::panel>
@endforeach

<x-mail::button :url="$checklistIndexUrl">
Abrir checklist documental
</x-mail::button>

Atentamente,  
Sistema de notificaciones **{{ config('app.name') }}**
</x-mail::message>
