@component('mail::message')
# Anulación {{ $approved ? 'aprobada' : 'rechazada' }}

**{{ $order->anulacionAprobador?->name ?? 'El director' }}** {{ $approved ? 'aprobó' : 'rechazó' }} la anulación de la **orden de compra N.º {{ $order->numeroOc }}** ({{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}).

@if ($approved)
La orden queda **Anulada** y su cantidad vuelve al saldo del pedido.
@else
La orden sigue **Emitida**.
@endif

@if (filled($order->anulacion_respuesta))
**Comentario del director:**

{{ $order->anulacion_respuesta }}
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
