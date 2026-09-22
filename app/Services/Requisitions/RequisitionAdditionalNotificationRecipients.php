<?php

namespace App\Services\Requisitions;

use App\Models\NotificationType;
use App\Models\PersonalRequisition;

/**
 * Destinatarios adicionales de requisiciones (alta normal + cambio de estado en Gestion).
 * No notifica cuando el tipo de cliente es Administrativos. Sin fallback de sistema.
 */
class RequisitionAdditionalNotificationRecipients
{
    public const EXCLUDED_CLIENT_TYPE_NAME = 'administrativos';

    /**
     * @param  list<string>  $exceptEmails  Correos ya notificados en el mismo evento (evitar duplicado).
     * @return list<string>
     */
    public function emailsFor(PersonalRequisition $requisition, array $exceptEmails = []): array
    {
        if ($this->isAdministrativosClientType($requisition)) {
            return [];
        }

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_REQUISITION_ADDITIONAL)
            ->first();

        if ($type === null) {
            return [];
        }

        $except = collect($exceptEmails)
            ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->unique()
            ->all();

        return $type->notificationEmails()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
            ->map(fn (string $email): string => trim($email))
            ->unique(fn (string $email): string => mb_strtolower($email))
            ->reject(fn (string $email): bool => in_array(mb_strtolower($email), $except, true))
            ->values()
            ->all();
    }

    public function isAdministrativosClientType(PersonalRequisition $requisition): bool
    {
        $requisition->loadMissing('clientType');

        $name = mb_strtolower(trim((string) ($requisition->clientType?->name ?? '')));

        return $name === self::EXCLUDED_CLIENT_TYPE_NAME;
    }
}
