@component('mail::message')
# Decisión sobre su solicitud de compra

Su solicitud **N.º {{ $purchaseRequest->folio() }}** fue **{{ $purchaseRequest->estadoLabel() }}** por **{{ $purchaseRequest->aprobador?->name ?? 'el director asignado' }}**.

@if (filled($purchaseRequest->comentarios_director))
**Observaciones del director:**

{{ $purchaseRequest->comentarios_director }}
@else
No se registraron observaciones adicionales del director.
@endif

@component('mail::button', ['url' => route('purchase-requests.show', ['module' => $purchaseRequest->area_key, 'purchase_request' => $purchaseRequest->id])])
Ver solicitud
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
