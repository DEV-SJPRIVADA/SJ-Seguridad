<?php

namespace App\Services\Requisitions;

use App\Models\RequisitionNotificationType;

class RequisitionNotificationRecipientService
{
    private const FALLBACK_EMAIL = 'desarrollo.tic@sjsp.com.co';

    /**
     * @return list<string>
     */
    public function emailsForType(string $slug): array
    {
        $type = RequisitionNotificationType::query()
            ->where('slug', $slug)
            ->first();

        if ($type === null) {
            return [self::FALLBACK_EMAIL];
        }

        $emails = $type->notificationEmails()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
            ->values()
            ->all();

        if ($emails === []) {
            return [self::FALLBACK_EMAIL];
        }

        return $emails;
    }

    /**
     * @return list<array{id: int, slug: string, label: string, description: ?string, email_ids: list<int>}>
     */
    public function typesWithAssignedEmailIds(): array
    {
        return RequisitionNotificationType::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (RequisitionNotificationType $type): array => [
                'id' => $type->id,
                'slug' => $type->slug,
                'label' => $type->label,
                'description' => $type->description,
                'email_ids' => $type->notificationEmails()->pluck('requisition_notification_emails.id')->map(fn ($id): int => (int) $id)->values()->all(),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $emailIds
     */
    public function syncTypeEmails(string $slug, array $emailIds): void
    {
        $type = RequisitionNotificationType::query()->where('slug', $slug)->firstOrFail();

        $validIds = RequisitionNotificationEmail::query()
            ->whereIn('id', $emailIds)
            ->pluck('id')
            ->all();

        $type->notificationEmails()->sync($validIds);
    }
}
