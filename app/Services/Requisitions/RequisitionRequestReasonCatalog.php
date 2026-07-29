<?php

namespace App\Services\Requisitions;

use App\Models\RequisitionRequestReason;

class RequisitionRequestReasonCatalog
{
    public const CARGO_NUEVO_REASON_NAME = 'cargo nuevo';

    public static function isCargoNuevoReasonId(?int $reasonId): bool
    {
        if ($reasonId === null || $reasonId <= 0) {
            return false;
        }

        $reason = RequisitionRequestReason::query()->find($reasonId);

        if ($reason === null) {
            return false;
        }

        return strtolower(trim((string) $reason->name)) === self::CARGO_NUEVO_REASON_NAME;
    }
}
