@component('mail::message')
# Solicitud de anulación de orden de compra

**{{ $order->anulacionSolicitadaPor?->name ?? 'Compras' }}** solicita anular la **orden de compra N.º {{ $order->numeroOc }}** y lo eligió a usted para aprobarlo.

**Origen:** {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}  
**Proveedor:** {{ $order->provider_nombre }}  
**Total:** {{ number_format((float) $order->total, 2) }}

**Motivo:**

{{ $order->anulacion_motivo }}

@component('mail::button', ['url' => $approvalUrl])
Revisar anulación
@endcomponent

También puede gestionarla en la plataforma, en **Solicitudes de compra → Pendientes de autorización**.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
