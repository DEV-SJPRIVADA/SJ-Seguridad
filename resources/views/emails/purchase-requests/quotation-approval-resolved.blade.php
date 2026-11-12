@component('mail::message')
# Cotizaciones {{ $approved ? 'aprobadas' : 'rechazadas' }}

El grupo **{{ $batchLabel }}** de la solicitud **N.º {{ $purchaseRequest->folioVisible() }}** fue **{{ $approved ? 'aprobado' : 'rechazado' }}**.

@if ($approved && $selected)
La cotización ganadora es **{{ $selected->descripcionVisible() }}** ({{ $selected->original_name }}). Al emitir la orden de compra puede vincularla desde la bandeja de Compras.
@endif

@if (filled($comment))
**Comentario del aprobador:**

{{ $comment }}
@endif

@if (! $approved)
Puede cargar otro grupo de cotizaciones o volver a solicitar la aprobación desde la bandeja de Compras.
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
