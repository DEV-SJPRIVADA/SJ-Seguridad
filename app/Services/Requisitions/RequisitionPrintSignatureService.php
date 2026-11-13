<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use App\Models\User;
use App\Services\Users\UserSignatureService;

/**
 * Firmas del FO-GH-22: líder = solicitante; GH = quien gestiona; gerencia = usuario
 * gerencia@sjsp.com.co solo si ya autorizó un cargo nuevo.
 */
class RequisitionPrintSignatureService
{
    public function __construct(
        private readonly UserSignatureService $signatures,
    ) {}

    /**
     * @return array{lider: ?string, gerencia: ?string, gh: ?string}
     */
    public function dataUris(?PersonalRequisition $requisition, bool $blank = false): array
    {
        $empty = [
            'lider' => null,
            'gerencia' => null,
            'gh' => null,
        ];

        if ($blank || $requisition === null || ! $requisition->exists) {
            return $empty;
        }

        $requisition->loadMissing(['requester', 'manager', 'statusLogs.author']);

        return [
            'lider' => $this->signatures->dataUri($requisition->requester),
            'gerencia' => $this->gerenciaUri($requisition),
            'gh' => $this->signatures->dataUri($this->ghUser($requisition)),
        ];
    }

    private function ghUser(PersonalRequisition $requisition): ?User
    {
        if ($requisition->manager !== null) {
            return $requisition->manager;
        }

        $log = $requisition->statusLogs
            ->where('to_status', PersonalRequisition::STATUS_EN_GESTION)
            ->sortBy('id')
            ->first();

        return $log?->author;
    }

    private function gerenciaUri(PersonalRequisition $requisition): ?string
    {
        $log = $requisition->managementApprovalDecisionLog();

        if ($log === null || $log->to_status !== PersonalRequisition::STATUS_SOLICITADA) {
            return null;
        }

        return $this->signatures->dataUri($this->signatures->gerenteUser());
    }
}
