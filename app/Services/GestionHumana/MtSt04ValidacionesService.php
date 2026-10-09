<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Cola Validaciones MT-ST-04: activos en Ficha que requieren psicofísicos
 * y aún no tienen fila en la matriz (o filtro de omitidos).
 */
final class MtSt04ValidacionesService
{
    /**
     * @param  array{q?: string|null, cola?: string|null}  $filters
     * @return Builder<EmployeeFichaProfile>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $cola = (string) ($filters['cola'] ?? 'pendientes');

        $query = EmployeeFichaProfile::query()
            ->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO)
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('mt_st_04_registros')
                    ->whereColumn('mt_st_04_registros.document_number', 'employee_ficha_profiles.document_number');
            });

        if ($cola === 'omitidos') {
            $query->where('requires_psicofisicos', false);
        } else {
            $query->where(function (Builder $inner): void {
                $inner->where('requires_psicofisicos', true)
                    ->orWhereNull('requires_psicofisicos');
            });
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('document_number', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('position_name', 'like', $like)
                    ->orWhere('work_city_name', 'like', $like)
                    ->orWhere('residence_city_name', 'like', $like)
                    ->orWhere('cost_center_name', 'like', $like);
            });
        }

        if ($ordered) {
            $query->orderBy('full_name')->orderBy('document_number');
        }

        return $query;
    }

    public function setRequiresPsicofisicos(string $documentNumber, bool $requires): ?EmployeeFichaProfile
    {
        $documentNumber = trim($documentNumber);
        if ($documentNumber === '') {
            return null;
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($profile === null) {
            return null;
        }

        $profile->requires_psicofisicos = $requires;
        $profile->save();

        return $profile->fresh();
    }

    public function isInMatriz(string $documentNumber): bool
    {
        return MtSt04Registro::query()
            ->where('document_number', trim($documentNumber))
            ->exists();
    }
}
