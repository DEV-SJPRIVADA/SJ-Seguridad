<?php

namespace App\Services\Admin;

use App\Models\User;

class UserManagementAuditService
{
    private const EVENT_TYPE = 'user_management';

    /**
     * @var array<int, string>
     */
    private const PROFILE_FIELDS = [
        'name',
        'email',
        'document_number',
        'area_key',
        'sede_id',
        'must_change_password',
    ];

    private const PERMISSIONS_DIFF_MAX = 50;

    public function __construct(
        private readonly AdminAuditLogService $auditLogService,
    ) {}

    public function logUserCreated(User $user, string $role, int $permissionsCount): void
    {
        $this->auditLogService->logModelChange(
            eventType: self::EVENT_TYPE,
            action: 'create',
            model: $user,
            before: null,
            after: $this->createSnapshot($user, $role),
            metadata: ['permissions_count' => $permissionsCount],
        );
    }

    /**
     * @param  array<string, mixed>  $beforeProfile
     * @param  array<int, string>  $beforePermissions
     * @param  array<int, string>  $newPermissions
     */
    public function logUserUpdated(
        User $user,
        array $beforeProfile,
        bool $beforeIsActive,
        string $beforeRole,
        array $beforePermissions,
        string $newRole,
        array $newPermissions,
        bool $passwordReset,
    ): void {
        $user->refresh();
        $user->load(['roles', 'permissions']);

        $this->logProfileUpdate($user, $beforeProfile);
        $this->logActiveStatusChange($user, $beforeIsActive);
        $this->logRoleSync($user, $beforeRole, $newRole);
        $this->logPermissionsSync($user, $beforePermissions, $newPermissions);

        if ($passwordReset) {
            $this->auditLogService->logModelChange(
                eventType: self::EVENT_TYPE,
                action: 'password_reset',
                model: $user,
                before: null,
                after: null,
                metadata: ['admin_initiated' => true],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function captureProfileState(User $user): array
    {
        return $this->extractProfileFields($user);
    }

    /**
     * @return array<int, string>
     */
    public function captureDirectPermissions(User $user): array
    {
        return $user->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    public function captureRole(User $user): string
    {
        return (string) $user->roles->pluck('name')->first();
    }

    /**
     * @param  array<string, mixed>  $beforeProfile
     */
    private function logProfileUpdate(User $user, array $beforeProfile): void
    {
        $afterProfile = $this->extractProfileFields($user);
        $oldValues = [];
        $newValues = [];

        foreach (self::PROFILE_FIELDS as $field) {
            $beforeValue = $beforeProfile[$field] ?? null;
            $afterValue = $afterProfile[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $oldValues[$field] = $beforeValue;
                $newValues[$field] = $afterValue;
            }
        }

        if ($oldValues === []) {
            return;
        }

        $this->auditLogService->logModelChange(
            eventType: self::EVENT_TYPE,
            action: 'update',
            model: $user,
            before: $oldValues,
            after: $newValues,
        );
    }

    private function logActiveStatusChange(User $user, bool $beforeIsActive): void
    {
        if ($beforeIsActive === (bool) $user->is_active) {
            return;
        }

        $this->auditLogService->logModelChange(
            eventType: self::EVENT_TYPE,
            action: $user->is_active ? 'activate' : 'deactivate',
            model: $user,
            before: null,
            after: null,
            metadata: ['previous_is_active' => $beforeIsActive],
        );
    }

    private function logRoleSync(User $user, string $beforeRole, string $newRole): void
    {
        if ($beforeRole === $newRole) {
            return;
        }

        $this->auditLogService->logModelChange(
            eventType: self::EVENT_TYPE,
            action: 'role_sync',
            model: $user,
            before: ['role' => $beforeRole],
            after: ['role' => $newRole],
        );
    }

    /**
     * @param  array<int, string>  $beforePermissions
     * @param  array<int, string>  $newPermissions
     */
    private function logPermissionsSync(User $user, array $beforePermissions, array $newPermissions): void
    {
        $added = array_values(array_diff($newPermissions, $beforePermissions));
        $removed = array_values(array_diff($beforePermissions, $newPermissions));

        if ($added === [] && $removed === []) {
            return;
        }

        sort($added);
        sort($removed);

        $this->auditLogService->logModelChange(
            eventType: self::EVENT_TYPE,
            action: 'permissions_sync',
            model: $user,
            before: null,
            after: null,
            metadata: $this->buildPermissionsMetadata($added, $removed),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createSnapshot(User $user, string $role): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'document_number' => $user->document_number,
            'area_key' => $user->area_key,
            'sede_id' => $user->sede_id,
            'is_active' => (bool) $user->is_active,
            'role' => $role,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractProfileFields(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'document_number' => $user->document_number,
            'area_key' => $user->area_key,
            'sede_id' => $user->sede_id,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }

    /**
     * @param  array<int, string>  $added
     * @param  array<int, string>  $removed
     * @return array<string, mixed>
     */
    private function buildPermissionsMetadata(array $added, array $removed): array
    {
        $addedCount = count($added);
        $removedCount = count($removed);
        [$cappedAdded, $cappedRemoved] = $this->capPermissionDiff($added, $removed);

        return [
            'added' => $cappedAdded,
            'removed' => $cappedRemoved,
            'added_count' => $addedCount,
            'removed_count' => $removedCount,
        ];
    }

    /**
     * @param  array<int, string>  $added
     * @param  array<int, string>  $removed
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function capPermissionDiff(array $added, array $removed): array
    {
        $total = count($added) + count($removed);

        if ($total <= self::PERMISSIONS_DIFF_MAX) {
            return [$added, $removed];
        }

        $remaining = self::PERMISSIONS_DIFF_MAX;
        $cappedAdded = array_slice($added, 0, $remaining);
        $remaining -= count($cappedAdded);
        $cappedRemoved = $remaining > 0 ? array_slice($removed, 0, $remaining) : [];

        return [$cappedAdded, $cappedRemoved];
    }
}
