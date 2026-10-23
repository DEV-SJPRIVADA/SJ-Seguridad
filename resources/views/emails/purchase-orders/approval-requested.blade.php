@component('mail::message')
# Aprobación de orden de compra

**{{ $order->aprobacionSolicitadaPor?->name ?? 'Compras' }}** solicita su aprobación para la **orden de compra N.º {{ $order->numeroOc }}**.

**Origen:** {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}  
**Proveedor:** {{ $order->provider_nombre }}  
**Total:** {{ number_format((float) $order->total, 2) }}

@if (filled($order->aprobacion_nota))
**Nota de Compras:**

{{ $order->aprobacion_nota }}
@endif

@component('mail::button', ['url' => $approvalUrl])
Revisar orden
@endcomponent

También puede gestionarla en la plataforma, en **Solicitudes de compra → Autorizaciones compras → Órdenes de Compra**.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
