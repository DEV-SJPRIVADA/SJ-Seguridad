<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeCursoListService
{
    /**
     * @param  array{
     *     document_number?: string|null,
     *     full_name?: string|null,
     *     curso_tipo_id?: int|string|null,
     *     vigencia?: string|null,
     *     estado?: string|null,
     *     solo_actualizar?: bool|string|null,
     *     fecha_desde?: string|null,
     *     fecha_hasta?: string|null,
     * }  $filters
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = EmployeeCurso::query()->with(['cursoTipo']);

        if ($ordered) {
            $query->orderByDesc('fecha_expedicion')->orderByDesc('id');
        }

        $cedula = trim((string) ($filters['document_number'] ?? ''));
        if ($cedula !== '') {
            $query->where('document_number', 'like', '%'.$cedula.'%');
        }

        $name = trim((string) ($filters['full_name'] ?? ''));
        if ($name !== '') {
            $query->where('full_name', 'like', '%'.$name.'%');
        }

        $tipoId = $filters['curso_tipo_id'] ?? null;
        if ($tipoId !== null && $tipoId !== '') {
            $query->where('curso_tipo_id', (int) $tipoId);
        }

        $estado = $filters['estado'] ?? null;
        if (is_string($estado) && $estado !== '' && $estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $fechaDesde = trim((string) ($filters['fecha_desde'] ?? ''));
        if ($fechaDesde !== '') {
            $query->whereDate('fecha_expedicion', '>=', $fechaDesde);
        }

        $fechaHasta = trim((string) ($filters['fecha_hasta'] ?? ''));
        if ($fechaHasta !== '') {
            $query->whereDate('fecha_expedicion', '<=', $fechaHasta);
        }

        $soloActualizar = filter_var($filters['solo_actualizar'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $vigencia = strtoupper(trim((string) ($filters['vigencia'] ?? '')));

        if ($soloActualizar) {
            $vigencia = 'ACTUALIZAR';
        }

        if (in_array($vigencia, EmployeeCurso::VIGENCIAS, true)) {
            $actualizarThreshold = EmployeeCurso::vigenciaThreshold(Carbon::today())->toDateString();
            $vencidoThreshold = EmployeeCurso::vencidoThreshold(Carbon::today())->toDateString();

            if ($vigencia === EmployeeCurso::VIGENCIA_VENCIDO) {
                $query->whereDate('fecha_expedicion', '<=', $vencidoThreshold);
            } elseif ($vigencia === EmployeeCurso::VIGENCIA_ACTUALIZAR) {
                $query->whereDate('fecha_expedicion', '<', $actualizarThreshold)
                    ->whereDate('fecha_expedicion', '>', $vencidoThreshold);
            } else {
                $query->whereDate('fecha_expedicion', '>=', $actualizarThreshold);
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EmployeeCurso>
     */
    public function all(array $filters): Collection
    {
        return $this->filteredQuery($filters)->get();
    }
}
