@component('mail::message')
# Orden de compra {{ $approved ? 'aprobada' : 'rechazada' }}

**{{ $order->aprobacionAprobador?->name ?? 'El aprobador' }}** {{ $approved ? 'aprobó' : 'rechazó' }} la **orden de compra N.º {{ $order->numeroOc }}** ({{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}).

@if ($approved)
La orden sigue **Emitida** y su PDF muestra la aprobación.
@else
La orden quedó **Anulada** y su cantidad volvió al saldo del pedido. Puede emitir una orden nueva corregida.
@endif

@if (filled($order->aprobacion_comentario))
**Comentario:**

{{ $order->aprobacion_comentario }}
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
