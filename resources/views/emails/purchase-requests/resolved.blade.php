@component('mail::message')
# Decisión sobre su solicitud de compra

Su solicitud **N.º {{ $purchaseRequest->folioVisible() }}** fue **{{ $purchaseRequest->estadoLabel() }}** por **{{ $purchaseRequest->aprobador?->name ?? 'el director asignado' }}**.

@php
    $observacionDirector = $purchaseRequest->comentarios_director
        ?: $purchaseRequest->comments->sortByDesc('id')->first()?->body;
@endphp
@if (filled($observacionDirector))
**Observaciones del director:**

{{ $observacionDirector }}
@else
No se registraron observaciones adicionales del director.
@endif

@component('mail::button', ['url' => route('purchase-requests.show', ['module' => $purchaseRequest->area_key, 'purchase_request' => $purchaseRequest->id])])
Ver solicitud
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
