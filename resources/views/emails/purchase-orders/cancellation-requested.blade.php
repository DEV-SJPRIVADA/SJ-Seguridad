@component('mail::message')
# Solicitud de anulación de orden de compra

**{{ $order->anulacionSolicitadaPor?->name ?? 'Compras' }}** solicita anular la **orden de compra N.º {{ $order->numeroOc }}** y lo eligió a usted para aprobarlo.

**Origen:** {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}  
**Proveedor:** {{ $order->provider_nombre }}  
**Total:** {{ \App\Support\ColombianCurrency::format($order->total) }}

**Motivo:**

{{ $order->anulacion_motivo }}

@component('mail::button', ['url' => $approvalUrl])
Revisar anulación
@endcomponent

También puede gestionarla en la plataforma, en **Solicitudes de compra → Autorizaciones compras → Anulaciones de órdenes**.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
