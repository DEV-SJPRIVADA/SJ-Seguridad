<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Matrices visibles para un usuario y usuarios con quienes se puede compartir.
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
     * Opciones de área para compartir: todas las áreas del sistema.
     *
     * @return array<string, string>
     */
    public function areaOptions(): array
    {
        return config('access.areas', []);
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
