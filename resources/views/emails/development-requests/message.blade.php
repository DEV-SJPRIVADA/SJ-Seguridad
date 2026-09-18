@component('mail::message')
# Mensaje en solicitud de desarrollo

**{{ $author->name }}** escribio en la solicitud **{{ $developmentRequest->code ?: ('#'.$developmentRequest->id) }}** — {{ $developmentRequest->title }}.

> {{ $excerpt }}

@component('mail::button', ['url' => $platformUrl])
Ver conversacion en StatFlow
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
