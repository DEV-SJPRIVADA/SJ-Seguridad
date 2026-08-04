@component('mail::message')
# Nueva solicitud de compra

Solicitud **N.º {{ $purchaseRequest->folio() }}** creada por **{{ $purchaseRequest->user?->name }}** y asignada a usted para autorizacion.

Puede revisar el detalle y registrar su decision directamente desde este correo (enlace personal con vigencia limitada) o ingresar a la plataforma.

Adjunto: PDF con el detalle completo de los articulos solicitados ({{ $formCode }}).

@component('mail::button', ['url' => $emailApprovalUrl])
Autorizar por correo
@endcomponent

@component('mail::button', ['url' => $platformUrl, 'color' => 'secondary'])
Ver en la plataforma
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
