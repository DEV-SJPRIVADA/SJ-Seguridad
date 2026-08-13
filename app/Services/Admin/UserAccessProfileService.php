<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserAccessProfileService
{
    /**
     * @return array{role: string, area_key: ?string, sede_id: ?int, permissions: array<int, string>}
     */
    public function extractProfile(User $user, bool $includeArea = true, bool $includeSede = true): array
    {
        return [
            'role' => (string) $user->roles->pluck('name')->first(),
            'area_key' => $includeArea ? $user->area_key : null,
            'sede_id' => $includeSede ? $user->sede_id : null,
            'permissions' => $user->permissions
                ->pluck('name')
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function copyCandidates(?User $exclude = null, ?User $actor = null): Collection
    {
        return User::query()
            ->with('roles')
            ->when($exclude !== null, fn ($query) => $query->whereKeyNot($exclude->getKey()))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'document_number', 'is_active'])
            ->when($actor !== null, fn (Collection $users) => $users->filter(
                fn (User $candidate): bool => $this->canCopyFrom($actor, $candidate),
            )->values());
    }

    public function canCopyFrom(User $actor, User $source): bool
    {
        return ! ($source->hasRole('super-admin') && ! $actor->hasRole('super-admin'));
    }

    public function canCopyTo(User $actor, User $target): bool
    {
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assertCanCopy(User $actor, User $source, User $target): void
    {
        if ($source->is($target)) {
            throw ValidationException::withMessages([
                'source_user_id' => 'No puede copiar acceso del mismo usuario.',
            ]);
        }

        if (! $this->canCopyFrom($actor, $source)) {
            throw new AuthorizationException('No tiene permiso para copiar acceso de ese usuario.');
        }

        if (! $this->canCopyTo($actor, $target)) {
            throw new AuthorizationException('No tiene permiso para modificar el acceso de ese usuario.');
        }

        $sourceRole = (string) $source->roles->pluck('name')->first();

        if ($sourceRole === 'super-admin' && ! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Solo un super-admin puede asignar el rol super-admin.');
        }
    }

    public function applyToUser(
        User $target,
        User $source,
        User $actor,
        bool $includeArea = true,
        bool $includeSede = true,
    ): void {
        $this->assertCanCopy($actor, $source, $target);

        $source->loadMissing(['roles', 'permissions']);
        $profile = $this->extractProfile($source, $includeArea, $includeSede);

        $attributes = [];

        if ($includeArea) {
            $attributes['area_key'] = $profile['area_key'];
        }

        if ($includeSede) {
            $attributes['sede_id'] = $profile['sede_id'];
        }

        if ($attributes !== []) {
            $target->update($attributes);
        }

        $target->syncRoles([$profile['role']]);
        $target->syncPermissions($profile['permissions']);
    }
}
