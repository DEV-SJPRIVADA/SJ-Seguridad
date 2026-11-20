<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta única de registros de una matriz.
 *
 * La usan el DataTable y el export para que ambos respeten exactamente
 * el mismo alcance (propios vs. todos).
 */
final class MatrixSubmissionQuery
{
    public function __construct(
        private readonly MatrixAccessResolver $accessResolver,
    ) {}

    /**
     * @return Builder<MatrixSubmission>
     *
     * @throws AuthorizationException
     */
    public function for(User $user, Matrix $matrix): Builder
    {
        $access = $this->accessResolver->resolve($user, $matrix);

        if (! $access->canView) {
            throw new AuthorizationException('No tienes acceso a esta matriz.');
        }

        return MatrixSubmission::query()
            ->where('matrix_id', $matrix->id)
            ->visibleTo($access->canViewOthers, $user->id);
    }
}
