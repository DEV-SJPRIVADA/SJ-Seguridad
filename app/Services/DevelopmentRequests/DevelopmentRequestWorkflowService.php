<?php

namespace App\Services\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DevelopmentRequestWorkflowService
{
    public function __construct(
        private readonly DevelopmentRequestAuditLogService $auditLogService,
    ) {}

    public function nextCode(): string
    {
        $year = now()->year;
        $prefix = 'DEV-'.$year.'-';

        $last = DevelopmentRequest::query()
            ->where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if (is_string($last) && preg_match('/DEV-\d{4}-(\d+)$/', $last, $m) === 1) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDraftOrSubmit(
        array $payload,
        User $actor,
        string $action,
        bool $actorIsSelectedLeader,
    ): DevelopmentRequest {
        return DB::transaction(function () use ($payload, $actor, $action, $actorIsSelectedLeader): DevelopmentRequest {
            $status = DevelopmentRequest::STATUS_BORRADOR;
            $code = null;
            $submittedAt = null;
            $radicatedAt = null;

            if ($action === 'submit') {
                $submittedAt = now();
                if ($actorIsSelectedLeader) {
                    $status = DevelopmentRequest::STATUS_RADICADO;
                    $code = $this->nextCode();
                    $radicatedAt = now();
                } else {
                    $status = DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER;
                }
            }

            $request = DevelopmentRequest::query()->create([
                ...$payload,
                'code' => $code,
                'status' => $status,
                'created_by' => $actor->id,
                'submitted_at' => $submittedAt,
                'radicated_at' => $radicatedAt,
                'leader_decided_at' => $status === DevelopmentRequest::STATUS_RADICADO ? now() : null,
            ]);

            $this->logStatus($request, null, $status, $actor, $action === 'submit' ? 'Envio de solicitud' : 'Borrador creado');

            $this->auditLogService->logEvent(
                eventType: 'development_request',
                action: $action === 'submit' ? 'store_submit' : 'store_draft',
                metadata: [
                    'development_request_id' => $request->id,
                    'code' => $request->code,
                    'status' => $request->status,
                ],
                model: $request,
                userId: $actor->id,
            );

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDraftOrResubmit(
        DevelopmentRequest $request,
        array $payload,
        User $actor,
        string $action,
        bool $actorIsSelectedLeader,
    ): DevelopmentRequest {
        if (! in_array($request->status, [
            DevelopmentRequest::STATUS_BORRADOR,
            DevelopmentRequest::STATUS_DEVUELTO,
        ], true)) {
            throw new InvalidArgumentException('Solo se pueden editar solicitudes en borrador o devueltas.');
        }

        return DB::transaction(function () use ($request, $payload, $actor, $action, $actorIsSelectedLeader): DevelopmentRequest {
            $from = $request->status;
            $request->fill($payload);

            if ($action === 'submit') {
                $request->submitted_at = now();
                if ($actorIsSelectedLeader) {
                    $request->status = DevelopmentRequest::STATUS_RADICADO;
                    $request->code ??= $this->nextCode();
                    $request->radicated_at = now();
                    $request->leader_decided_at = now();
                } else {
                    $request->status = DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER;
                    $request->radicated_at = null;
                    $request->leader_decided_at = null;
                }
            }

            $request->save();

            if ($from !== $request->status) {
                $this->logStatus($request, $from, $request->status, $actor, 'Actualizacion / reenvio');
            }

            $this->auditLogService->logEvent(
                eventType: 'development_request',
                action: 'update',
                metadata: [
                    'development_request_id' => $request->id,
                    'status' => $request->status,
                ],
                model: $request,
                userId: $actor->id,
            );

            return $request->fresh();
        });
    }

    public function leaderDecide(
        DevelopmentRequest $request,
        User $leader,
        string $decision,
        ?string $notes,
    ): DevelopmentRequest {
        if ($request->status !== DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER) {
            throw new InvalidArgumentException('La solicitud no esta pendiente de aprobacion del lider.');
        }

        if ((int) $request->leader_id !== (int) $leader->id && ! $leader->hasRole('super-admin') && ! $leader->can('manage.users')) {
            throw new InvalidArgumentException('Solo el lider asignado puede decidir.');
        }

        return DB::transaction(function () use ($request, $leader, $decision, $notes): DevelopmentRequest {
            $from = $request->status;

            if ($decision === 'approve') {
                $request->status = DevelopmentRequest::STATUS_RADICADO;
                $request->code ??= $this->nextCode();
                $request->radicated_at = now();
                $request->leader_decided_at = now();
                $request->leader_decision_notes = $notes;
                $request->rejection_reason = null;
            } else {
                $request->status = DevelopmentRequest::STATUS_RECHAZADO;
                $request->leader_decided_at = now();
                $request->leader_decision_notes = $notes;
                $request->rejection_reason = $notes;
                $request->closed_at = now();
            }

            $request->save();
            $this->logStatus($request, $from, $request->status, $leader, $notes);

            $this->auditLogService->logEvent(
                eventType: 'development_request',
                action: $decision === 'approve' ? 'leader_approve' : 'leader_reject',
                metadata: [
                    'development_request_id' => $request->id,
                    'code' => $request->code,
                    'status' => $request->status,
                ],
                model: $request,
                userId: $leader->id,
            );

            return $request->fresh();
        });
    }

    /**
     * Transiciones TIC / UAT.
     *
     * @param  array<string, mixed>  $ticFields
     */
    public function transition(
        DevelopmentRequest $request,
        User $actor,
        string $toStatus,
        ?string $comment = null,
        array $ticFields = [],
    ): DevelopmentRequest {
        $from = $request->status;
        $allowed = $this->allowedTransitions($from);

        if (! in_array($toStatus, $allowed, true)) {
            throw new InvalidArgumentException("Transicion no permitida: {$from} → {$toStatus}.");
        }

        return DB::transaction(function () use ($request, $actor, $from, $toStatus, $comment, $ticFields): DevelopmentRequest {
            if ($ticFields !== []) {
                $request->fill($ticFields);
            }

            $request->status = $toStatus;

            if ($toStatus === DevelopmentRequest::STATUS_DEVUELTO) {
                $request->radicated_at = null;
            }

            if ($toStatus === DevelopmentRequest::STATUS_RECHAZADO) {
                $request->rejection_reason = $comment;
                $request->closed_at = now();
            }

            if ($toStatus === DevelopmentRequest::STATUS_CERRADO) {
                $request->closed_at = now();
            }

            $request->save();
            $this->logStatus($request, $from, $toStatus, $actor, $comment);

            $this->auditLogService->logEvent(
                eventType: 'development_request',
                action: 'tic_transition',
                metadata: [
                    'development_request_id' => $request->id,
                    'from' => $from,
                    'to' => $toStatus,
                ],
                model: $request,
                userId: $actor->id,
            );

            return $request->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateTicBlock(DevelopmentRequest $request, User $actor, array $fields): DevelopmentRequest
    {
        return DB::transaction(function () use ($request, $actor, $fields): DevelopmentRequest {
            $request->fill($fields);
            $request->save();

            $this->auditLogService->logEvent(
                eventType: 'development_request',
                action: 'tic_block_update',
                metadata: ['development_request_id' => $request->id],
                model: $request,
                userId: $actor->id,
            );

            return $request->fresh();
        });
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(string $from): array
    {
        return match ($from) {
            DevelopmentRequest::STATUS_RADICADO => [
                DevelopmentRequest::STATUS_EN_ANALISIS,
                DevelopmentRequest::STATUS_DEVUELTO,
                DevelopmentRequest::STATUS_RECHAZADO,
            ],
            DevelopmentRequest::STATUS_EN_ANALISIS => [
                DevelopmentRequest::STATUS_EN_DESARROLLO,
                DevelopmentRequest::STATUS_DEVUELTO,
                DevelopmentRequest::STATUS_RECHAZADO,
            ],
            DevelopmentRequest::STATUS_EN_DESARROLLO => [
                DevelopmentRequest::STATUS_EN_PRUEBAS,
                DevelopmentRequest::STATUS_EN_ANALISIS,
            ],
            DevelopmentRequest::STATUS_EN_PRUEBAS => [
                DevelopmentRequest::STATUS_ENTREGADO,
                DevelopmentRequest::STATUS_EN_DESARROLLO,
            ],
            DevelopmentRequest::STATUS_ENTREGADO => [
                DevelopmentRequest::STATUS_CERRADO,
            ],
            default => [],
        };
    }

    public function logStatus(
        DevelopmentRequest $request,
        ?string $from,
        string $to,
        User $actor,
        ?string $comment = null,
    ): void {
        $request->statusLogs()->create([
            'user_id' => $actor->id,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
        ]);
    }
}
