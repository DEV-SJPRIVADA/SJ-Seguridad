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
    public function visibleMatrices(User $user, array $filters = []): Collection
    {
        return $this->visibleQuery($user, $filters)
            ->with(['owner:id,name', 'currentVersion', 'areaAccess', 'userAccess'])
            ->withCount(['submissions' => fn (Builder $query) => $query->whereNull('archived_at')])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return Builder<Matrix>
     */
    public function visibleQuery(User $user, array $filters = []): Builder
    {
        $query = Matrix::query()->notArchived();

        if ($this->accessResolver->isManager($user)) {
            // Los administradores ven todas las matrices del área/sistema.
        } else {
            $query = $this->ownedOrGrantedQuery($query, $user);
        }

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $area = trim((string) ($filters['area'] ?? ''));
        if ($area !== '') {
            $query->where('area_key', $area);
        }

        $owner = (int) ($filters['owner'] ?? 0);
        if ($owner > 0) {
            $query->where('owner_user_id', $owner);
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, [Matrix::STATUS_DRAFT, Matrix::STATUS_PUBLISHED], true)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Matrices que se muestran como pestañas en el subnav.
     *
     * Regla de negocio: solo las matrices que el usuario **creó o le compartieron**
     * (por usuario o por área), incluso si es administrador. El resto de matrices
     * del área queda accesible desde las tarjetas del índice, no como pestañas,
     * para que el subnav no crezca sin límite.
     *
     * @return Collection<int, Matrix>
     */
    public function tabbedMatrices(User $user): Collection
    {
        return $this->ownedOrGrantedQuery(Matrix::query()->notArchived(), $user)
            ->with('currentVersion:id,version_number')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status', 'area_key', 'owner_user_id', 'current_version_id', 'archived_at']);
    }

    /**
     * Matrices propias o compartidas con el usuario, con los filtros de «Mis matrices».
     *
     * @param  array{q?: string|null, area?: string|null, owner?: int|string|null, status?: string|null}  $filters
     * @return Collection<int, Matrix>
     */
    public function mineMatrices(User $user, array $filters = []): Collection
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $area = trim((string) ($filters['area'] ?? ''));
        $owner = (int) ($filters['owner'] ?? 0);
        $status = (string) ($filters['status'] ?? '');

        return $this->ownedOrGrantedQuery(Matrix::query()->notArchived(), $user)
            ->with(['currentVersion:id,version_number', 'owner:id,name', 'updatedBy:id,name'])
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($term): void {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            }))
            ->when($area !== '', fn (Builder $query) => $query->where('area_key', $area))
            ->when($owner > 0, fn (Builder $query) => $query->where('owner_user_id', $owner))
            ->when(
                in_array($status, [Matrix::STATUS_DRAFT, Matrix::STATUS_PUBLISHED], true),
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * Filtro de propiedad u otorgamiento vigente, sin el bypass de administrador.
     *
     * @param  Builder<Matrix>  $query
     * @return Builder<Matrix>
     */
    private function ownedOrGrantedQuery(Builder $query, User $user): Builder
    {
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
     * El listado de tarjetas (administración de matrices) es solo para quien
     * puede crear matrices.
     */
    public function canSeeAdminCards(User $user): bool
    {
        return $user->can('matrices.manage') || $this->accessResolver->isManager($user);
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
