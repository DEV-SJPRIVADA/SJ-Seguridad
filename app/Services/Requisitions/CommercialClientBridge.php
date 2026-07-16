<?php

namespace App\Services\Requisitions;

use App\Models\CommercialClient;
use App\Models\PersonalRequisition;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;

class CommercialClientBridge
{
    public const INTERNAL_REQUISITION_CLIENT_NAME = 'Cliente interno SJ Seguridad';

    public static function isInternalClientType(?int $clientTypeId): bool
    {
        if (! $clientTypeId) {
            return false;
        }

        $name = RequisitionClientType::query()->whereKey($clientTypeId)->value('name');

        return strtolower(trim((string) $name)) === 'interno';
    }

    public static function resolveInternalClientId(): int
    {
        $existing = RequisitionClient::query()
            ->where('name', self::INTERNAL_REQUISITION_CLIENT_NAME)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $existing->id;
        }

        $maxSort = (int) (RequisitionClient::query()->max('sort_order') ?? 0);

        return RequisitionClient::query()->create([
            'name' => self::INTERNAL_REQUISITION_CLIENT_NAME,
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ])->id;
    }

    public static function resolve(int $commercialClientId): int
    {
        $commercial = CommercialClient::query()->findOrFail($commercialClientId);

        $existing = RequisitionClient::query()
            ->where('name', $commercial->name)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $existing->id;
        }

        $maxSort = (int) (RequisitionClient::query()->max('sort_order') ?? 0);

        return RequisitionClient::query()->create([
            'name' => $commercial->name,
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ])->id;
    }

    public static function findForRequisition(?PersonalRequisition $requisition): ?CommercialClient
    {
        $fromOld = old('commercial_client_id');
        if ($fromOld) {
            return CommercialClient::query()->find((int) $fromOld);
        }

        if (! $requisition?->client) {
            return null;
        }

        return CommercialClient::query()
            ->where('name', $requisition->client->name)
            ->orderBy('id')
            ->first();
    }
}
