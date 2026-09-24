<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcreditacionAcreditadoListService
{
    /**
     * @param  array{
     *     document_number?: string|null,
     *     cargo?: string|null,
     *     cargo_apo?: string|null,
     *     estado?: string|null,
     *     vigencia_desde?: string|null,
     *     vigencia_hasta?: string|null,
     *     ficha_estado?: string|null,
     * }  $filters
     * @return Builder<AcreditacionAcreditado>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = AcreditacionAcreditado::query();

        if ($ordered) {
            $query->orderByDesc('id');
        }

        $cedula = trim((string) ($filters['document_number'] ?? ''));
        if ($cedula !== '') {
            $query->where('document_number', 'like', '%'.$cedula.'%');
        }

        $cargo = trim((string) ($filters['cargo'] ?? ''));
        if ($cargo !== '') {
            $query->where('cargo', 'like', '%'.$cargo.'%');
        }

        $cargoApo = trim((string) ($filters['cargo_apo'] ?? ''));
        if ($cargoApo !== '') {
            $query->whereRaw('LOWER(TRIM(cargo_apo)) = ?', [mb_strtolower($cargoApo)]);
        }

        $estado = (string) ($filters['estado'] ?? '');
        if ($estado !== '' && $estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $vigenciaDesde = trim((string) ($filters['vigencia_desde'] ?? ''));
        if ($vigenciaDesde !== '') {
            $query->whereDate('vigencia_acr', '>=', $vigenciaDesde);
        }

        $vigenciaHasta = trim((string) ($filters['vigencia_hasta'] ?? ''));
        if ($vigenciaHasta !== '') {
            $query->whereDate('vigencia_acr', '<=', $vigenciaHasta);
        }

        $this->applyFichaEstadoFilter($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFichaEstadoFilter(Builder $query, array $filters): void
    {
        $fichaEstado = (string) ($filters['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO);

        if ($fichaEstado === '' || $fichaEstado === 'todos') {
            return;
        }

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        $query->whereExists(function ($sub) use ($fichaEstado): void {
            $sub->selectRaw('1')
                ->from('employee_ficha_profiles')
                ->whereColumn(
                    'employee_ficha_profiles.document_number',
                    'acreditacion_acreditados.document_number',
                )
                ->where('employee_ficha_profiles.employment_status', $fichaEstado);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AcreditacionAcreditado>
     */
    public function all(array $filters): Collection
    {
        return $this->filteredQuery($filters)->get();
    }
}
