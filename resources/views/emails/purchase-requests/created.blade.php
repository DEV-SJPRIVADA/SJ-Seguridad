@component('mail::message')
# Nueva solicitud de compra

Solicitud **N.º {{ $purchaseRequest->folio() }}** creada por **{{ $purchaseRequest->user?->name }}**.

@component('mail::button', ['url' => $approvalUrl])
Revisar y autorizar
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
