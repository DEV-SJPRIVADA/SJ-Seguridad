@php
    $esPasoArea = $paso === \App\Services\PurchaseRequests\PurchaseOrderApprovalService::PASO_AREA;
@endphp
@component('mail::message')
# Aprobación de orden de compra

**{{ $order->aprobacionSolicitadaPor?->name ?? 'Compras' }}** solicita su aprobación para la **orden de compra N.º {{ $order->numeroOc }}**.

@if ($esPasoArea)
Usted autorizó la solicitud de origen. Este es el **paso 1**: el visto bueno del área. El Director Admon. y Financiero no puede autorizar la orden hasta que usted decida.
@elseif ($yaPuedeDecidir)
El área ya dio su visto bueno. Este es el **paso 2**: su autorización como Director Admon. y Financiero.
@else
Este es el **paso 2** (Director Admon. y Financiero). Debe esperar el visto bueno de **{{ $order->aprobacionAreaAprobador?->name ?? 'quien autorizó la solicitud' }}** antes de decidir.
@endif

**Origen:** {{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}  
**Proveedor:** {{ $order->provider_nombre }}  
**Total:** {{ number_format((float) $order->total, 2) }}
@if ($order->purchaseQuotation)

**Cotización aprobada:** {{ $order->purchaseQuotation->descripcionVisible() }} · {{ $order->purchaseQuotation->loteLabel() }}  
**Cotización aprobada por:** {{ $order->purchaseQuotation->aprobador?->name ?? '—' }}
@endif

@if (filled($order->aprobacion_nota))
**Nota de Compras:**

{{ $order->aprobacion_nota }}
@endif

@component('mail::button', ['url' => $approvalUrl])
Revisar orden
@endcomponent

También puede gestionarla en la plataforma, en **Solicitudes de compra → Autorizaciones compras → Órdenes de Compra**@if ($esPasoArea && $order->source_type === \App\Services\PurchaseRequests\PurchaseOrderService::SOURCE_SUPPLY) o en **Por aprobar** de insumos @endif.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
