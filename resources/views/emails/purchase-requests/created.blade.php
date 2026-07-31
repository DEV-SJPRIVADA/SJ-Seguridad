@component('mail::message')
# Nueva solicitud de compra

Solicitud **N.º {{ $purchaseRequest->folio() }}** creada por **{{ $purchaseRequest->user?->name }}** y asignada a usted para autorizacion.

Ingrese a la plataforma, revise el detalle y registre su decision desde **Solicitudes de compra → Pendientes de autorizacion**.

@component('mail::button', ['url' => $approvalUrl])
Ver solicitud en la plataforma
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
