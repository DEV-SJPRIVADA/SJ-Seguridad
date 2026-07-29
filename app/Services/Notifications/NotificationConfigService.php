<?php

namespace App\Services\Notifications;

use App\Models\NotificationEmail;
use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Collection;

class NotificationConfigService
{
    /**
     * @return list<string>
     */
    public function recipientEmails(string $module, string $slug): array
    {
        $type = NotificationType::query()
            ->where('module', $module)
            ->where('slug', $slug)
            ->first();

        if ($type === null) {
            return [$this->fallbackEmail()];
        }

        $emails = $type->notificationEmails()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return [$this->fallbackEmail()];
        }

        return $emails;
    }

    /**
     * @return list<array{module: string, module_label: string, types: list<array{id: int, slug: string, label: string, description: ?string, email_ids: list<int>}>}>
     */
    public function typesGroupedByModule(bool $adminConfigurableOnly = false): array
    {
        $moduleLabels = config('notifications.modules', []);
        $configurable = config('notifications.admin_configurable', []);

        return NotificationType::query()
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->when($adminConfigurableOnly, function (Collection $types) use ($configurable): Collection {
                return $types->filter(function (NotificationType $type) use ($configurable): bool {
                    return in_array($type->slug, $configurable[$type->module] ?? [], true);
                });
            })
            ->groupBy('module')
            ->map(function (Collection $types, string $module) use ($moduleLabels): array {
                return [
                    'module' => $module,
                    'module_label' => $moduleLabels[$module] ?? $module,
                    'types' => $types->map(fn (NotificationType $type): array => [
                        'id' => $type->id,
                        'slug' => $type->slug,
                        'label' => $type->label,
                        'description' => $type->description,
                        'emails' => $type->notificationEmails()
                            ->orderBy('notification_emails.name')
                            ->get(['notification_emails.id', 'notification_emails.name'])
                            ->unique('id')
                            ->values()
                            ->map(fn (NotificationEmail $email): array => [
                                'id' => $email->id,
                                'name' => $email->name,
                            ])
                            ->all(),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function isAdminConfigurable(NotificationType $type): bool
    {
        $configurable = config('notifications.admin_configurable', []);

        return in_array($type->slug, $configurable[$type->module] ?? [], true);
    }

    public function addEmailToType(NotificationType $type, string $address): void
    {
        $normalized = strtolower(trim($address));

        $email = NotificationEmail::query()->firstOrCreate(
            ['name' => $normalized],
            [
                'is_active' => true,
                'sort_order' => ((int) NotificationEmail::query()->max('sort_order')) + 1,
            ]
        );

        if (! $email->is_active) {
            $email->update(['is_active' => true]);
        }

        if ($type->notificationEmails()->where('notification_emails.id', $email->id)->exists()) {
            return;
        }

        $type->notificationEmails()->attach($email->id);
    }

    public function removeEmailFromType(NotificationType $type, NotificationEmail $email): void
    {
        if (! $type->notificationEmails()->where('notification_emails.id', $email->id)->exists()) {
            return;
        }

        $type->notificationEmails()->detach($email->id);

        if ($email->notificationTypes()->doesntExist()) {
            $email->delete();
        }
    }

    private function fallbackEmail(): string
    {
        return (string) config('notifications.fallback_recipient', 'desarrollo.tic@sjsp.com.co');
    }
}
