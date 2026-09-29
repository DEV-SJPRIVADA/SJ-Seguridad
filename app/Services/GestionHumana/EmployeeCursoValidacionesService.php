<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Support\DocumentNumberListParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class EmployeeCursoValidacionesService
{
    public const COLA_SIN_CURSO = 'sin_curso';

    public const COLA_POR_ACTUALIZAR_VENCIDOS = 'por_actualizar_vencidos';

    /**
     * @var list<string>
     */
    public const COLAS = [
        self::COLA_SIN_CURSO,
        self::COLA_POR_ACTUALIZAR_VENCIDOS,
    ];

    public function __construct(
        private readonly DocumentNumberListParser $documentNumberListParser,
    ) {}

    public function isValidCola(string $cola): bool
    {
        return in_array($cola, self::COLAS, true);
    }

    /**
     * @return array<string, string>
     */
    public function colaLabels(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('cursos.validaciones.colas', []);

        return $labels;
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return [
            self::COLA_SIN_CURSO => $this->sinCursoQuery([])->count(),
            self::COLA_POR_ACTUALIZAR_VENCIDOS => $this->porActualizarVencidosQuery([])->count(),
        ];
    }

    /**
     * @param  array{
     *     document_number?: string|null,
     *     document_numbers?: list<string>|string|null,
     *     full_name?: string|null,
     *     curso_tipo_id?: int|string|null,
     *     estado?: string|null,
     *     vigencia?: string|null,
     * }  $filters
     */
    public function sinCursoQuery(array $filters): Builder
    {
        $query = EmployeeFichaProfile::query()
            ->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO)
            ->where('requires_courses', true)
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_cursos')
                    ->whereColumn('employee_cursos.document_number', 'employee_ficha_profiles.document_number');
            });

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

        $name = trim((string) ($filters['full_name'] ?? ''));
        if ($name !== '') {
            $query->where('full_name', 'like', '%'.$name.'%');
        }

        return $query->orderBy('full_name')->orderBy('document_number');
    }

    /**
     * @param  array{
     *     document_number?: string|null,
     *     document_numbers?: list<string>|string|null,
     *     full_name?: string|null,
     *     curso_tipo_id?: int|string|null,
     *     estado?: string|null,
     *     vigencia?: string|null,
     * }  $filters
     */
    public function porActualizarVencidosQuery(array $filters, bool $ordered = true): Builder
    {
        $actualizarThreshold = EmployeeCurso::vigenciaThreshold(Carbon::today())->toDateString();

        $query = EmployeeCurso::query()
            ->with(['cursoTipo', 'cursoEscuela'])
            ->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('employee_ficha_profiles as efp')
                    ->whereColumn('efp.document_number', 'employee_cursos.document_number')
                    ->where('efp.employment_status', EmployeeFichaProfile::STATUS_ACTIVO)
                    ->where('efp.requires_courses', true);
            })
            ->where(function ($q) use ($actualizarThreshold): void {
                $q->whereNull('fecha_expedicion')
                    ->orWhereDate('fecha_expedicion', '<', $actualizarThreshold);
            });

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

        $vigencia = strtoupper(trim((string) ($filters['vigencia'] ?? '')));
        if (in_array($vigencia, [EmployeeCurso::VIGENCIA_ACTUALIZAR, EmployeeCurso::VIGENCIA_VENCIDO], true)) {
            $vencidoThreshold = EmployeeCurso::vencidoThreshold(Carbon::today())->toDateString();
            if ($vigencia === EmployeeCurso::VIGENCIA_VENCIDO) {
                $query->whereDate('fecha_expedicion', '<=', $vencidoThreshold);
            } else {
                $query->where(function ($q) use ($actualizarThreshold, $vencidoThreshold): void {
                    $q->whereNull('fecha_expedicion')
                        ->orWhere(function ($inner) use ($actualizarThreshold, $vencidoThreshold): void {
                            $inner->whereDate('fecha_expedicion', '<', $actualizarThreshold)
                                ->whereDate('fecha_expedicion', '>', $vencidoThreshold);
                        });
                });
            }
        }

        if ($ordered) {
            $query->orderByDesc('fecha_expedicion')->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function exportColumns(string $cola): array
    {
        return match ($cola) {
            self::COLA_SIN_CURSO => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'position_name', 'label' => 'Cargo'],
            ],
            self::COLA_POR_ACTUALIZAR_VENCIDOS => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'tipo_curso', 'label' => 'Tipo curso'],
                ['key' => 'numero_curso', 'label' => 'No.CURSO'],
                ['key' => 'fecha_expedicion', 'label' => 'Fecha expedición'],
                ['key' => 'vigencia', 'label' => 'Vigencia'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            default => [
                ['key' => 'document_number', 'label' => 'Cédula'],
            ],
        };
    }
}
