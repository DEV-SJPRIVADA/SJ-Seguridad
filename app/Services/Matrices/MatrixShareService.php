<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Compartir una matriz por usuario o por área.
 *
 * Reglas: "editar registros de otros" implica "ver registros de otros".
 * Compartir sin casillas deja el acceso en solo lectura.
 */
final class MatrixShareService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertUser(Matrix $matrix, array $data, User $actor): MatrixUserAccess
    {
        $flags = $this->normalizeFlags($data);

        /** @var MatrixUserAccess $grant */
        $grant = MatrixUserAccess::query()->updateOrCreate(
            [
                'matrix_id' => $matrix->id,
                'user_id' => (int) $data['user_id'],
            ],
            [
                ...$flags,
                'granted_by_user_id' => $actor->id,
            ],
        );

        return $grant;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertArea(Matrix $matrix, array $data, User $actor): MatrixAreaAccess
    {
        $flags = $this->normalizeFlags($data);

        /** @var MatrixAreaAccess $grant */
        $grant = MatrixAreaAccess::query()->updateOrCreate(
            [
                'matrix_id' => $matrix->id,
                'area_key' => (string) $data['area_key'],
            ],
            [
                ...$flags,
                'granted_by_user_id' => $actor->id,
            ],
        );

        return $grant;
    }

    public function removeUser(MatrixUserAccess $grant): void
    {
        $grant->delete();
    }

    public function removeArea(MatrixAreaAccess $grant): void
    {
        $grant->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{can_register: bool, can_view_others: bool, can_edit_others: bool, expires_at: ?Carbon}
     */
    private function normalizeFlags(array $data): array
    {
        $canRegister = (bool) ($data['can_register'] ?? false);
        $canEditOthers = (bool) ($data['can_edit_others'] ?? false);
        $canViewOthers = (bool) ($data['can_view_others'] ?? false) || $canEditOthers;

        $expiresAt = null;

        if (! empty($data['expires_at'])) {
            $expiresAt = Carbon::parse((string) $data['expires_at'])->endOfDay();
        }

        return [
            'can_register' => $canRegister,
            'can_view_others' => $canViewOthers,
            'can_edit_others' => $canEditOthers,
            'expires_at' => $expiresAt,
        ];
    }
}
