@component('mail::message')
# Solicitud de compra actualizada

Su solicitud **N.º {{ $purchaseRequest->folio() }}** fue **{{ $purchaseRequest->estado }}** por {{ $purchaseRequest->aprobador?->name }}.

@if ($purchaseRequest->comentarios_director)
**Comentarios:** {{ $purchaseRequest->comentarios_director }}
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
