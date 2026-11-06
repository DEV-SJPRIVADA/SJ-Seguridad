<?php

namespace App\Services\Calidad;

use App\Models\ServicioClienteCaso;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServicioClienteDatatableService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ServicioClienteCaso>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = ServicioClienteCaso::query()->with([
            'tipologia:id,name',
            'fuente:id,name',
            'categoria:id,name',
            'responsable:id,name',
            'ciudad:id,name',
        ])->withCount('observaciones');

        if ($ordered) {
            $query->orderByDesc('consecutivo');
        }

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function filtersFromRequest(Request $request): array
    {
        return [
            'tipo_cliente' => $request->string('tipo_cliente')->toString(),
            'tipologia_id' => $request->string('tipologia_id')->toString(),
            'fuente_id' => $request->string('fuente_id')->toString(),
            'responsable_id' => $request->string('responsable_id')->toString(),
            'eficacia' => $request->string('eficacia')->toString(),
            'recepcion_desde' => $request->string('recepcion_desde')->toString(),
            'recepcion_hasta' => $request->string('recepcion_hasta')->toString(),
            'verificacion_desde' => $request->string('verificacion_desde')->toString(),
            'verificacion_hasta' => $request->string('verificacion_hasta')->toString(),
            'plazo_desde' => $request->string('plazo_desde')->toString(),
            'plazo_hasta' => $request->string('plazo_hasta')->toString(),
            'recurrencia' => $request->string('recurrencia')->toString() ?: 'mes',
        ];
    }

    /**
     * Listado FO-GI-22: sin eficacia en la URL se asume Abierto. «todos» no filtra.
     *
     * @return array<string, mixed>
     */
    public function listFiltersFromRequest(Request $request): array
    {
        $filters = $this->filtersFromRequest($request);
        if (($filters['eficacia'] ?? '') === '') {
            $filters['eficacia'] = ServicioClienteCaso::EFICACIA_ABIERTO;
        }

        return $filters;
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void
    {
        $tipo = (string) ($filters['tipo_cliente'] ?? '');
        if (in_array($tipo, [ServicioClienteCaso::TIPO_INTERNO, ServicioClienteCaso::TIPO_EXTERNO], true)) {
            $query->where('tipo_cliente', $tipo);
        }

        foreach ([
            'tipologia_id' => 'tipologia_id',
            'fuente_id' => 'fuente_id',
            'responsable_id' => 'responsable_id',
        ] as $key => $column) {
            $value = $filters[$key] ?? '';
            if ($value !== null && $value !== '') {
                $query->where($column, (int) $value);
            }
        }

        $eficacia = (string) ($filters['eficacia'] ?? '');
        if ($eficacia !== 'todos' && array_key_exists($eficacia, config('servicio_cliente.eficacia', []))) {
            $query->where('eficacia', $eficacia);
        }

        $this->applyDateRange($query, 'fecha_recepcion', $filters['recepcion_desde'] ?? '', $filters['recepcion_hasta'] ?? '');
        $this->applyDateRange($query, 'fecha_verificacion', $filters['verificacion_desde'] ?? '', $filters['verificacion_hasta'] ?? '');
        $this->applyDateRange($query, 'fecha_plazo_respuesta', $filters['plazo_desde'] ?? '', $filters['plazo_hasta'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
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
        }

        $data = $query->get()->map(fn (ServicioClienteCaso $caso): array => $this->formatRow($caso, $canEdit))->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     */
    private function applyDateRange(Builder $query, string $column, mixed $from, mixed $until): void
    {
        $from = trim((string) $from);
        $until = trim((string) $until);

        if ($from !== '') {
            $query->whereDate($column, '>=', $from);
        }

        if ($until !== '') {
            $query->whereDate($column, '<=', $until);
        }
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like, $search): void {
            $inner->where('cliente_nombre', 'like', $like)
                ->orWhere('descripcion', 'like', $like)
                ->orWhere('correccion', 'like', $like)
                ->orWhere('accion_realizar', 'like', $like)
                ->orWhere('responsable_seguimiento', 'like', $like)
                ->orWhere('eficacia', 'like', $like);

            if (ctype_digit($search)) {
                $inner->orWhere('consecutivo', (int) $search);
            }

            $inner->orWhereHas('tipologia', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('fuente', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('categoria', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('responsable', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('ciudad', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('observaciones', fn (Builder $rel) => $rel->where('texto', 'like', $like));
        });
    }

    /**
     * @param  Builder<ServicioClienteCaso>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('consecutivo');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('consecutivo', $direction),
            1 => $query->orderBy('fecha_recepcion', $direction),
            2 => $query->orderBy('tipo_cliente', $direction),
            5 => $query->orderBy('cliente_nombre', $direction),
            12 => $query->orderBy('fecha_ejecucion', $direction),
            13 => $query->orderBy('fecha_verificacion', $direction),
            14 => $query->orderBy('fecha_plazo_respuesta', $direction),
            15 => $query->orderBy('dias_habiles', $direction),
            18 => $query->orderBy('eficacia', $direction),
            default => $query->orderByDesc('consecutivo'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(ServicioClienteCaso $caso, bool $canEdit): array
    {
        $showUrl = route('calidad.servicio-cliente.fo-gi-22.show', $caso);

        $cells = [
            e((string) $caso->consecutivo),
            e(DisplayDate::date($caso->fecha_recepcion)),
            e($caso->tipoClienteLabel()),
            e((string) ($caso->tipologia?->name ?: '—')),
            e((string) ($caso->fuente?->name ?: '—')),
            e((string) $caso->cliente_nombre),
            e((string) ($caso->ciudad?->name ?: '—')),
            e($this->clip($caso->descripcion)),
            e((string) ($caso->categoria?->name ?: '—')),
            e($this->clip($caso->correccion)),
            e($this->clip($caso->accion_realizar)),
            e((string) ($caso->responsable?->name ?: '—')),
            e(DisplayDate::date($caso->fecha_ejecucion)),
            e(DisplayDate::date($caso->fecha_verificacion)),
            e(DisplayDate::date($caso->fecha_plazo_respuesta)),
            e($caso->dias_habiles !== null ? (string) $caso->dias_habiles : '—'),
            e($this->clip((string) ($caso->responsable_seguimiento ?? ''))),
            e((string) $caso->observaciones_count),
            e($caso->eficaciaLabel()),
        ];

        $cells[] = $this->formatActionsCell($caso, $showUrl, $canEdit);

        return $cells;
    }

    private function clip(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '—';
        }

        return mb_strlen($value) > 80 ? mb_substr($value, 0, 77).'…' : $value;
    }

    private function formatActionsCell(ServicioClienteCaso $caso, string $showUrl, bool $canEdit): string
    {
        $openLabel = $canEdit ? 'Editar' : 'Consultar';
        $icon = $canEdit
            ? '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>'
            : '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';

        $html = '<div class="table-actions">'.
            '<a href="'.e($showUrl).'" class="cursos-catalogo-page__icon-btn" title="'.e($openLabel).'" aria-label="'.e($openLabel).'">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$icon.'</svg>'.
            '</a>';

        if ($canEdit) {
            $html .= '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger js-fo-gi-22-delete" '.
                'data-delete-url="'.e(route('calidad.servicio-cliente.fo-gi-22.destroy', $caso)).'" '.
                'data-delete-label="'.e('N° '.$caso->consecutivo).'" title="Eliminar" aria-label="Eliminar">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
                '</button>';
        }

        return $html.'</div>';
    }
}
