@props(['requisition', 'statusLabels' => []])

@php
    $decisionLog = $requisition->managementApprovalDecisionLog();
@endphp

@if ($decisionLog)
    <div class="req-approval-letter__panel req-approval-letter__panel--decision">
        <p class="req-approval-letter__section-label" style="margin-top: 0;">Decision de gerencia</p>
        <p>
            <strong>Resultado:</strong>
            {{ $statusLabels[$decisionLog->to_status] ?? $decisionLog->to_status }}
        </p>
        @if (filled($decisionLog->comment))
            <p><strong>Observacion de gerencia:</strong> {{ $decisionLog->comment }}</p>
        @endif
        <p class="req-approval-letter__decision-meta">
            {{ $decisionLog->author?->name ?? 'Sistema' }}
            @if ($decisionLog->created_at)
                · {{ $decisionLog->created_at->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>
@endif
