<?php

namespace App\Services\Requisitions;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class RequisitionSelectionOfficerAccessService
{
    public const AREA_KEY = 'gestion_humana';

    public const PERMISSION = 'requisitions.selection_officer';

    /**
     * @return Collection<int, User>
     */
    public function gestionHumanaAreaUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('area_key', self::AREA_KEY)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function selectableSelectionOfficers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('area_key', self::AREA_KEY)
            ->permission(self::PERMISSION)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function recruitersForSelect(?int $currentRecruiterId = null): Collection
    {
        $users = $this->selectableSelectionOfficers();

        if ($currentRecruiterId === null || $users->pluck('id')->contains($currentRecruiterId)) {
            return $users;
        }

        $current = User::query()->find($currentRecruiterId);

        if ($current === null) {
            return $users;
        }

        return $users->push($current)->sortBy('name')->values();
    }

    public function canActAsSelectionOfficer(User $user): bool
    {
        if (! $user->is_active || $user->area_key !== self::AREA_KEY) {
            return false;
        }

        return $user->can(self::PERMISSION);
    }

    public function isAllowedRecruiterId(int $userId, ?int $existingRecruiterId): bool
    {
        if (! User::query()->whereKey($userId)->exists()) {
            return false;
        }

        if ($existingRecruiterId !== null && $userId === $existingRecruiterId) {
            return true;
        }

        $user = User::query()->find($userId);

        return $user !== null && $this->canActAsSelectionOfficer($user);
    }

    public function setSelectionOfficerEnabled(User $user, bool $enabled): void
    {
        if ($user->area_key !== self::AREA_KEY || ! $user->is_active) {
            throw new \InvalidArgumentException('El usuario no pertenece al area Gestion humana activa.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($enabled) {
            $user->givePermissionTo(self::PERMISSION);
        } else {
            $user->revokePermissionTo(self::PERMISSION);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
