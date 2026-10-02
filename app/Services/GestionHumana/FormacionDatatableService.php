<?php

namespace App\Services\GestionHumana;

use App\Models\FormacionRegistro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class FormacionDatatableService
{
    /**
     * @var array<int, string>
     */
    public const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    public function __construct(
        private readonly FormacionDashboardService $dashboardService,
    ) {}

    /**
     * @param  array{
     *     anio?: int|string|null,
     *     mes?: int|string|null,
     *     categoria?: string|null,
     *     nombre_curso?: string|null,
     *     numero_id?: string|null,
     *     nombre?: string|null,
     *     estado?: string|null,
     *     ciclo?: string|null,
     * }  $filters
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = FormacionRegistro::query();

        if ($ordered) {
            $query->orderByDesc('fecha_inicio')->orderByDesc('id');
        }

        $anio = $filters['anio'] ?? null;
        $anioInt = null;
        if ($anio !== null && $anio !== '') {
            $anioInt = (int) $anio;
            $query->where('anio', $anioInt);
        }

        $mes = $filters['mes'] ?? null;
        $mesInt = null;
        if ($mes !== null && $mes !== '') {
            $mesCandidate = (int) $mes;
            if ($mesCandidate >= 1 && $mesCandidate <= 12) {
                $mesInt = $mesCandidate;
                $query->where('mes', $mesInt);
            }
        }

        $categoria = trim((string) ($filters['categoria'] ?? ''));
        if ($categoria !== '') {
            $query->where('categoria', $categoria);
        }

        $nombreCurso = trim((string) ($filters['nombre_curso'] ?? ''));
        if ($nombreCurso !== '') {
            $query->where('nombre_curso', $nombreCurso);
        }

        $numeroId = trim((string) ($filters['numero_id'] ?? ''));
        if ($numeroId !== '') {
            $query->where('numero_id', 'like', '%'.$numeroId.'%');
        }

        $nombre = trim((string) ($filters['nombre'] ?? ''));
        if ($nombre !== '') {
            $query->where('nombre_completo', 'like', '%'.$nombre.'%');
        }

        $ciclo = strtolower(trim((string) ($filters['ciclo'] ?? '')));
        if ($ciclo !== '' && array_key_exists($ciclo, FormacionRegistro::CICLO_LABELS)) {
            if ($anioInt === null || $anioInt < 2000 || $anioInt > 2100) {
                $query->whereRaw('0 = 1');
            } else {
                $personIds = $this->dashboardService->personIdsForCiclo($anioInt, $mesInt, $ciclo);
                if ($personIds === []) {
                    $query->whereRaw('0 = 1');
                } else {
                    $query->whereIn('numero_id', $personIds);
                }
            }

            // El filtro ciclo ya acota personas; no mezclar con estado de registro.
            return $query;
        }

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $query->withEstado($estado);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = $this->filteredQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, FormacionRegistro> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (FormacionRegistro $registro): array => $this->formatRow($registro))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Opciones distinct indexadas para searchable-select (no 81k filas crudas).
     * Cursos: distinct del año (y mes si se indica), mismo criterio que Dashboard.
     *
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     categorias: list<array{value: string, label: string}>,
     *     cursos: list<array{value: string, label: string}>,
     *     estados: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(?int $anio = null, ?int $mes = null): array
    {
        $anios = FormacionRegistro::query()
            ->select('anio')
            ->whereNotNull('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anioValue): array => [
                'value' => (string) $anioValue,
                'label' => (string) $anioValue,
            ])
            ->values()
            ->all();

        $meses = [];
        foreach (self::MESES as $value => $label) {
            $meses[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        $categorias = FormacionRegistro::query()
            ->select('categoria')
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->map(fn ($categoria): array => [
                'value' => (string) $categoria,
                'label' => (string) $categoria,
            ])
            ->values()
            ->all();

        $estados = [];
        foreach (FormacionRegistro::ESTADO_LABELS as $value => $label) {
            $estados[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return [
            'anios' => $anios,
            'meses' => $meses,
            'categorias' => $categorias,
            'cursos' => $this->courseOptions($anio, $mes),
            'estados' => $estados,
        ];
    }

    /**
     * Cursos distinct del año (y mes si se indica). Sin año → todos; sin mes → todos del año.
     *
     * @return list<array{value: string, label: string}>
     */
    public function courseOptions(?int $anio = null, ?int $mes = null): array
    {
        $query = FormacionRegistro::query()
            ->select('nombre_curso')
            ->whereNotNull('nombre_curso')
            ->where('nombre_curso', '!=', '')
            ->distinct()
            ->orderBy('nombre_curso');

        if ($anio !== null && $anio >= 2000 && $anio <= 2100) {
            $query->where('anio', $anio);
        }

        if ($mes !== null && $mes >= 1 && $mes <= 12) {
            $query->where('mes', $mes);
        }

        return $query
            ->pluck('nombre_curso')
            ->map(static fn ($curso): array => [
                'value' => (string) $curso,
                'label' => (string) $curso,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Builder<FormacionRegistro>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('numero_id', 'like', $like)
                ->orWhere('nombre_completo', 'like', $like)
                ->orWhere('nombre_curso', 'like', $like)
                ->orWhere('categoria', 'like', $like)
                ->orWhere('calificacion', 'like', $like);
        });
    }

    /**
     * @param  Builder<FormacionRegistro>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_inicio')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('numero_id', $direction),
            1 => $query->orderBy('nombre_completo', $direction),
            2 => $query->orderBy('fecha_inicio', $direction)->orderBy('id', $direction),
            3 => $query->orderBy('mes', $direction),
            4 => $query->orderBy('anio', $direction),
            5 => $query->orderBy('nombre_curso', $direction),
            6 => $query->orderBy('calificacion', $direction),
            // Estado es derivado de calificación; ordenar por la misma columna.
            7 => $query->orderBy('calificacion', $direction),
            8 => $query->orderBy('categoria', $direction),
            default => $query->orderByDesc('fecha_inicio')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(FormacionRegistro $registro): array
    {
        $mes = (int) $registro->mes;
        $mesLabel = self::MESES[$mes] ?? (string) $mes;

        return [
            e((string) $registro->numero_id),
            e((string) $registro->nombre_completo),
            e(optional($registro->fecha_inicio)?->format('Y-m-d') ?: '—'),
            e($mesLabel),
            e((string) $registro->anio),
            e((string) $registro->nombre_curso),
            e((string) ($registro->calificacion !== null && $registro->calificacion !== '' ? $registro->calificacion : '—')),
            sprintf(
                '<span class="%s">%s</span>',
                e($registro->estadoCssClass()),
                e($registro->estadoLabel()),
            ),
            e((string) $registro->categoria),
        ];
    }
}
