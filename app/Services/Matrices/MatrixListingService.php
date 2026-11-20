<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Matrices visibles para un usuario y usuarios asignables a un registro.
 */
final class MatrixListingService
{
    public function __construct(
        private readonly MatrixAccessResolver $accessResolver,
    ) {}

    /**
     * @return Collection<int, Matrix>
     */
    public function visibleMatrices(User $user): Collection
    {
        return $this->visibleQuery($user)
            ->with(['owner:id,name', 'currentVersion', 'areaAccess', 'userAccess'])
            ->withCount(['submissions' => fn (Builder $query) => $query->whereNull('archived_at')])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return Builder<Matrix>
     */
    public function visibleQuery(User $user): Builder
    {
        $query = Matrix::query()->notArchived();

        if ($this->accessResolver->isManager($user)) {
            return $query;
        }

        $areaKey = $user->hasAssignedArea() ? $user->area_key : null;

        return $query->where(function (Builder $inner) use ($user, $areaKey): void {
            $inner->where('owner_user_id', $user->id)
                ->orWhereHas('userAccess', fn (Builder $grant) => $grant
                    ->where('user_id', $user->id)
                    ->active());

            if ($areaKey !== null) {
                $inner->orWhereHas('areaAccess', fn (Builder $grant) => $grant
                    ->where('area_key', $areaKey)
                    ->active());
            }
        });
    }

    /**
     * Usuarios que pueden quedar asignados a un registro de la matriz:
     * el creador, los usuarios con acceso individual y los miembros de las áreas compartidas.
     *
     * @return Collection<int, User>
     */
    public function assignableUsers(Matrix $matrix): Collection
    {
        $userIds = MatrixUserAccess::query()
            ->where('matrix_id', $matrix->id)
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $areaKeys = MatrixAreaAccess::query()
            ->where('matrix_id', $matrix->id)
            ->pluck('area_key')
            ->all();

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($matrix, $userIds, $areaKeys): void {
                $query->whereKey($matrix->owner_user_id);

                if ($userIds !== []) {
                    $query->orWhereIn('id', $userIds);
                }

                if ($areaKeys !== []) {
                    $query->orWhereIn('area_key', $areaKeys);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'area_key']);
    }

    /**
     * Opciones de área para compartir: todas las áreas del sistema.
     *
     * @return array<string, string>
     */
    public function areaOptions(): array
    {
        return config('access.areas', []);
    }

    /**
     * Opciones legibles de usuarios para los filtros del listado.
     *
     * @return array<int, string>
     */
    public function userOptions(Matrix $matrix): array
    {
        return $this->assignableUsers($matrix)
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])
            ->all();
    }

    /**
     * Usuarios activos que se pueden compartir (todos excepto el creador).
     *
     * @return Collection<int, User>
     */
    public function shareableUsers(Matrix $matrix): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->when($matrix->owner_user_id !== null, fn (Builder $query) => $query->whereKeyNot($matrix->owner_user_id))
            ->orderBy('name')
            ->get(['id', 'name', 'area_key']);
    }
}
