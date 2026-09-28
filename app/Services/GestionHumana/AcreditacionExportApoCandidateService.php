<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AcreditacionExportApoCandidateService
{
    /**
     * @var list<string>
     */
    public const CANDIDATE_ESTADOS = [
        AcreditacionAcreditado::ESTADO_EN_PROCESO,
        AcreditacionAcreditado::ESTADO_POR_VENCER,
        AcreditacionAcreditado::ESTADO_DESACREDITADO,
    ];

    /**
     * Universo candidatos: estados ley 3 + Ficha activa (employment_status = activo).
     *
     * @return Builder<AcreditacionAcreditado>
     */
    public function candidateQuery(bool $ordered = true): Builder
    {
        $query = AcreditacionAcreditado::query()
            ->whereIn('estado', self::CANDIDATE_ESTADOS)
            ->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_ficha_profiles')
                    ->whereColumn(
                        'employee_ficha_profiles.document_number',
                        'acreditacion_acreditados.document_number',
                    )
                    ->where('employee_ficha_profiles.employment_status', EmployeeFichaProfile::STATUS_ACTIVO);
            });

        if ($ordered) {
            $query->orderBy('document_number')->orderBy('cargo_apo')->orderBy('id');
        }

        return $query;
    }

    /**
     * @return Collection<int, AcreditacionAcreditado>
     */
    public function allCandidates(): Collection
    {
        return $this->candidateQuery(ordered: true)->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, AcreditacionAcreditado>
     */
    public function findCandidatesByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return collect();
        }

        return $this->candidateQuery(ordered: false)
            ->whereIn('id', $ids)
            ->orderBy('document_number')
            ->orderBy('cargo_apo')
            ->orderBy('id')
            ->get();
    }

    /**
     * Carga acreditados por ID sin filtrar por universo candidato (preview forzado).
     *
     * @param  list<int>  $ids
     * @return Collection<int, AcreditacionAcreditado>
     */
    public function findByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return collect();
        }

        return AcreditacionAcreditado::query()
            ->whereIn('id', $ids)
            ->orderBy('document_number')
            ->orderBy('cargo_apo')
            ->orderBy('id')
            ->get();
    }
}
