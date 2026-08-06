@props(['requisition', 'statusLabels' => []])

<div class="req-approval-letter__panel">
    <p><strong>Codigo:</strong> {{ $requisition->code }}</p>
    <p><strong>Motivo:</strong> {{ $requisition->requestReason?->name ?? '—' }}</p>
    <p><strong>Cargo:</strong> {{ $requisition->position?->name ?? '—' }}</p>
    <p><strong>Solicitante:</strong> {{ $requisition->requester?->name ?? '—' }}</p>
    <p><strong>Area solicitante:</strong> {{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}</p>
    <p><strong>Estado:</strong> {{ $statusLabels[$requisition->status] ?? $requisition->status }}</p>
    @if ($requisition->request_date)
        <p><strong>Fecha solicitud:</strong> {{ $requisition->request_date->format('d/m/Y') }}</p>
    @endif
</div>

<div class="req-approval-letter__panel req-approval-letter__panel--muted">
    <p><strong>Cliente:</strong> {{ $requisition->client?->name ?? '—' }}</p>
    <p><strong>Ciudad:</strong> {{ $requisition->city?->name ?? '—' }}</p>
    @if ($requisition->clientType?->name)
        <p><strong>Tipo de cliente:</strong> {{ $requisition->clientType->name }}</p>
    @endif
    @if ($requisition->programmingType?->name)
        <p><strong>Programacion:</strong> {{ $requisition->programmingType->name }}</p>
    @endif
    @if ($requisition->uniform?->name)
        <p><strong>Dotacion:</strong> {{ $requisition->uniform->name }}</p>
    @endif
    <p><strong>Perfil requerido:</strong> {{ $requisition->required_profile }}</p>
    @if (filled($requisition->service_structure))
        <p><strong>Estructura del servicio:</strong> {{ $requisition->service_structure }}</p>
    @endif
    @if ($requisition->requester_observation)
        <p><strong>Observacion del solicitante:</strong> {{ $requisition->requester_observation }}</p>
    @endif
</div>
