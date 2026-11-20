<?php

namespace App\Http\Controllers\Matrices\Concerns;

use App\Models\Matrices\Matrix;
use App\Models\User;
use App\Services\Access\MatrixAccessService;
use App\Traits\ValidatesModule;
use Illuminate\Support\Facades\Gate;

trait ResolvesMatrixContext
{
    use ValidatesModule;

    /**
     * Valida el área de la URL y que el usuario tenga el tablero Matrices visible.
     */
    protected function authorizeBoard(string $module): User
    {
        $this->abortIfUnknownModule($module);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        abort_unless(app(MatrixAccessService::class)->canViewBoard($user, $module), 403);

        return $user;
    }

    protected function authorizeMatrix(string $ability, Matrix $matrix, mixed ...$arguments): void
    {
        Gate::authorize($ability, [$matrix, ...$arguments]);
    }
}
