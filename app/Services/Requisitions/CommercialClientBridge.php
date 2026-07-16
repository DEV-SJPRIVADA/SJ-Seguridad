<?php

namespace App\Services\Requisitions;

use App\Models\CommercialClient;
use App\Models\PersonalRequisition;
use App\Models\RequisitionClient;

class CommercialClientBridge
{
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
