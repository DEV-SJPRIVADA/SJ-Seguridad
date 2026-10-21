@component('mail::message')
# Compras devolvió su solicitud de compra

Su solicitud **N.º {{ $purchaseRequest->folioVisible() }}** fue **devuelta por Compras** para que la corrija.

**Motivo:**

{{ $motivo }}

Corríjala desde **Mis solicitudes** y envíela de nuevo. Al reenviarla, el director debe volver a autorizarla.

@component('mail::button', ['url' => route('purchase-requests.show', ['module' => $purchaseRequest->area_key, 'purchase_request' => $purchaseRequest->id, 'from' => 'mis_solicitudes'])])
Ver solicitud
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
