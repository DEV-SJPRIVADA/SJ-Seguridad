@component('mail::message')
# Aprobación de cotizaciones

**{{ $quotations->first()?->solicitadaPor?->name ?? 'Compras' }}** solicita su aprobación para el grupo **{{ $batchLabel }}** de la **solicitud N.º {{ $purchaseRequest->folioVisible() }}**.

Este grupo representa una orden de compra futura y lleva {{ $quotations->count() }} cotización(es):

@foreach ($quotations as $quotation)
- **{{ $quotation->descripcionVisible() }}** — archivo {{ $quotation->original_name }}
@endforeach

Abra el enlace para previsualizar los documentos, elegir la cotización ganadora y aprobar, o rechazar el grupo completo. El enlace es personal y tiene vigencia limitada.

@component('mail::button', ['url' => $approvalUrl])
Revisar cotizaciones
@endcomponent

También puede gestionarlo en la plataforma, en **Solicitudes de compra → Autorizaciones compras → Cotizaciones**.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
