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

    /**
     * @param  array{
     *     anio?: int|string|null,
     *     mes?: int|string|null,
     *     categoria?: string|null,
     *     nombre_curso?: string|null,
     *     numero_id?: string|null,
     *     nombre?: string|null,
     * }  $filters
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = FormacionRegistro::query();

        if ($ordered) {
            $query->orderByDesc('fecha_inicio')->orderByDesc('id');
        }

        $anio = $filters['anio'] ?? null;
        if ($anio !== null && $anio !== '') {
            $query->where('anio', (int) $anio);
        }

        $mes = $filters['mes'] ?? null;
        if ($mes !== null && $mes !== '') {
            $mesInt = (int) $mes;
            if ($mesInt >= 1 && $mesInt <= 12) {
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
     *
     * @return array{
     *     anios: list<array{value: string, label: string}>,
     *     meses: list<array{value: string, label: string}>,
     *     categorias: list<array{value: string, label: string}>,
     *     cursos: list<array{value: string, label: string}>
     * }
     */
    public function filterSelectOptions(): array
    {
        $anios = FormacionRegistro::query()
            ->select('anio')
            ->whereNotNull('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anio): array => [
                'value' => (string) $anio,
                'label' => (string) $anio,
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

        $cursos = FormacionRegistro::query()
            ->select('nombre_curso')
            ->whereNotNull('nombre_curso')
            ->where('nombre_curso', '!=', '')
            ->distinct()
            ->orderBy('nombre_curso')
            ->pluck('nombre_curso')
            ->map(fn ($curso): array => [
                'value' => (string) $curso,
                'label' => (string) $curso,
            ])
            ->values()
            ->all();

        return [
            'anios' => $anios,
            'meses' => $meses,
            'categorias' => $categorias,
            'cursos' => $cursos,
        ];
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
            7 => $query->orderBy('categoria', $direction),
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
            e((string) $registro->categoria),
        ];
    }
}
