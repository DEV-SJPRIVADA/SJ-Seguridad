<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
use App\Support\DocumentNumberListParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcreditacionAcreditadoListService
{
    public function __construct(
        private readonly DocumentNumberListParser $documentNumberListParser,
    ) {}

    /**
     * @param  array{
     *     document_number?: string|null,
     *     document_numbers?: list<string>|string|null,
     *     cargo?: string|null,
     *     cargo_apo?: string|null,
     *     estado?: string|null,
     *     renovacion?: string|null,
     *     vigencia_desde?: string|null,
     *     vigencia_hasta?: string|null,
     *     fecha_solicitud_desde?: string|null,
     *     fecha_solicitud_hasta?: string|null,
     *     ficha_estado?: string|null,
     *     en_perfil?: string|null,
     * }  $filters
     * @param  list<int>  $pinnedIds  IDs editados que deben seguir visibles aunque no cumplan el filtro.
     * @return Builder<AcreditacionAcreditado>
     */
    public function filteredQuery(array $filters, bool $ordered = true, array $pinnedIds = []): Builder
    {
        $query = AcreditacionAcreditado::query();

        if ($ordered) {
            $query->orderByDesc('id');
        }

        if ($pinnedIds === []) {
            $this->applyFilters($query, $filters);

            return $query;
        }

        $query->where(function (Builder $inner) use ($filters, $pinnedIds): void {
            $this->applyFilters($inner, $filters);
            $inner->orWhereIn('id', $pinnedIds);
        });

        return $query;
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Listado: todos (con/sin perfil). Chips `en_perfil` + `ficha_estado` acotan.
        $this->applyEnPerfilFilter($query, (string) ($filters['en_perfil'] ?? ''));

        $cedula = trim((string) ($filters['document_number'] ?? ''));
        if ($cedula !== '') {
            $query->where('document_number', 'like', '%'.$cedula.'%');
        }

        $documentNumbers = $this->documentNumberListParser->fromInput($filters['document_numbers'] ?? null);
        if ($documentNumbers !== []) {
            $query->whereIn(
                'document_number',
                $this->documentNumberListParser->lookupValues($documentNumbers),
            );
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

        $renovacion = (string) ($filters['renovacion'] ?? '');
        if ($renovacion !== '' && $renovacion !== 'todos') {
            $query->where('renovacion', $renovacion);
        }

        $vigenciaDesde = trim((string) ($filters['vigencia_desde'] ?? ''));
        if ($vigenciaDesde !== '') {
            $query->whereDate('vigencia_acr', '>=', $vigenciaDesde);
        }

        $vigenciaHasta = trim((string) ($filters['vigencia_hasta'] ?? ''));
        if ($vigenciaHasta !== '') {
            $query->whereDate('vigencia_acr', '<=', $vigenciaHasta);
        }

        // Drill-down desde Dashboard (fecha solicitud).
        $solicitudDesde = trim((string) ($filters['fecha_solicitud_desde'] ?? ''));
        if ($solicitudDesde !== '') {
            $query->whereDate('fecha_solicitud', '>=', $solicitudDesde);
        }

        $solicitudHasta = trim((string) ($filters['fecha_solicitud_hasta'] ?? ''));
        if ($solicitudHasta !== '') {
            $query->whereDate('fecha_solicitud', '<=', $solicitudHasta);
        }

        $this->applyFichaEstadoFilter($query, $filters);
    }

    /**
     * Filtro chip: `1`/`si` = tiene fila en employee_ficha_profiles; `0`/`no` = sin perfil; vacío = todos.
     *
     * @param  Builder<AcreditacionAcreditado>  $query
     */
    private function applyEnPerfilFilter(Builder $query, string $raw): void
    {
        $normalized = strtolower(trim($raw));

        if (in_array($normalized, ['1', 'si', 'sí', 'true'], true)) {
            $query->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_ficha_profiles')
                    ->whereColumn(
                        'employee_ficha_profiles.document_number',
                        'acreditacion_acreditados.document_number',
                    );
            });

            return;
        }

        if (in_array($normalized, ['0', 'no', 'false'], true)) {
            $query->whereNotExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_ficha_profiles')
                    ->whereColumn(
                        'employee_ficha_profiles.document_number',
                        'acreditacion_acreditados.document_number',
                    );
            });
        }
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFichaEstadoFilter(Builder $query, array $filters): void
    {
        // Sin perfil no tiene employment_status; el chip `en_perfil=0` ya acotó el universo.
        $enPerfil = strtolower(trim((string) ($filters['en_perfil'] ?? '')));
        if (in_array($enPerfil, ['0', 'no', 'false'], true)) {
            return;
        }

        $fichaEstado = (string) ($filters['ficha_estado'] ?? 'todos');

        if ($fichaEstado === '' || $fichaEstado === 'todos') {
            return;
        }

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
        ], true)) {
            return;
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
     * @param  list<int>  $pinnedIds
     * @return Collection<int, AcreditacionAcreditado>
     */
    public function all(array $filters, array $pinnedIds = []): Collection
    {
        return $this->filteredQuery($filters, pinnedIds: $pinnedIds)->get();
    }
}
