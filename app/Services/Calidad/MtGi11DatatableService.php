<?php

namespace App\Services\Calidad;

use App\Models\Calidad\MtGi11Solicitud;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Filtros, búsqueda y orden del listado MT-GI-11 (server-side).
 *
 * El alcance por usuario se aplica aquí: sin `view_all` solo se devuelven las
 * solicitudes creadas por el usuario autenticado.
 */
final class MtGi11DatatableService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<MtGi11Solicitud>
     */
    public function filteredQuery(array $filters, int $userId, bool $viewAll, bool $ordered = true): Builder
    {
        $query = MtGi11Solicitud::query()
            ->visibleTo($userId, $viewAll)
            ->with([
                'proceso:id,name',
                'tipoDocumento:id,name',
                'tipoSolicitud:id,name',
                'cargoResponsable:id,name',
                'estado:id,name,color',
                'creator:id,name',
            ])
            ->withCount('adjuntos');

        if ($ordered) {
            $query->orderByDesc('fecha_solicitud')->orderByDesc('id');
        }

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Filtros del listado y del export (mismo mapa en ambos).
     *
     * @return array<string, mixed>
     */
    public function filtersFromRequest(Request $request): array
    {
        return [
            'fecha_desde' => $request->string('fecha_desde')->toString(),
            'fecha_hasta' => $request->string('fecha_hasta')->toString(),
            'proceso_id' => $request->string('proceso_id')->toString(),
            'consecutivo' => $request->string('consecutivo')->toString(),
            'tipo_documento_id' => $request->string('tipo_documento_id')->toString(),
            'estado_id' => $request->string('estado_id')->toString(),
            'q' => $request->string('q')->toString(),
        ];
    }

    /**
     * Filtros activos listos para URL (sin valores vacíos).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function activeQuery(array $filters): array
    {
        return array_filter(
            array_map(fn ($value): string => (string) $value, $filters),
            static fn (string $value): bool => $value !== '',
        );
    }

    /**
     * @param  Builder<MtGi11Solicitud>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void
    {
        $from = trim((string) ($filters['fecha_desde'] ?? ''));
        if ($from !== '') {
            $query->whereDate('fecha_solicitud', '>=', $from);
        }

        $until = trim((string) ($filters['fecha_hasta'] ?? ''));
        if ($until !== '') {
            $query->whereDate('fecha_solicitud', '<=', $until);
        }

        foreach (['proceso_id', 'tipo_documento_id', 'estado_id'] as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $query->where($key, (int) $value);
            }
        }

        // El consecutivo puede repetirse: se filtra por coincidencia parcial.
        $consecutivo = trim((string) ($filters['consecutivo'] ?? ''));
        if ($consecutivo !== '') {
            $query->where('consecutivo', 'like', '%'.$consecutivo.'%');
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $this->applyGlobalSearch($query, $search);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, int $userId, bool $viewAll, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = $this->filteredQuery($filters, $userId, $viewAll, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        }

        $data = $query->get()
            ->map(fn (MtGi11Solicitud $solicitud): array => $this->formatRow($solicitud, $canEdit))
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @param  Builder<MtGi11Solicitud>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search !== '') {
            $this->applyGlobalSearch($query, $search);
        }
    }

    /**
     * Búsqueda libre sobre todos los campos del registro y sus catálogos.
     *
     * @param  Builder<MtGi11Solicitud>  $query
     */
    private function applyGlobalSearch(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like, $search): void {
            $inner->where('consecutivo', 'like', $like)
                ->orWhere('nombre_documento', 'like', $like)
                ->orWhere('codigo_actual', 'like', $like)
                ->orWhere('version_actual', 'like', $like)
                ->orWhere('justificacion', 'like', $like)
                ->orWhere('responsable_proceso', 'like', $like)
                ->orWhere('codigo_estandarizacion', 'like', $like)
                ->orWhereHas('proceso', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('tipoDocumento', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('tipoSolicitud', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('cargoResponsable', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('estado', fn (Builder $rel) => $rel->where('name', 'like', $like))
                ->orWhereHas('creator', fn (Builder $rel) => $rel->where('name', 'like', $like));

            if (ctype_digit($search)) {
                $inner->orWhere('version_actualizada', (int) $search);
            }
        });
    }

    /**
     * Columnas de la tabla en el mismo orden que `matriz.blade.php`.
     *
     * @param  Builder<MtGi11Solicitud>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_solicitud')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('fecha_solicitud', $direction)->orderBy('id', $direction),
            1 => $query->orderBy('consecutivo', $direction),
            2 => $query->orderBy('proceso_id', $direction),
            3 => $query->orderBy('tipo_documento_id', $direction),
            4 => $query->orderBy('nombre_documento', $direction),
            5 => $query->orderBy('tipo_solicitud_id', $direction),
            6 => $query->orderBy('codigo_actual', $direction),
            7 => $query->orderBy('version_actual', $direction),
            8 => $query->orderBy('responsable_proceso', $direction),
            9 => $query->orderBy('cargo_responsable_id', $direction),
            10 => $query->orderBy('codigo_estandarizacion', $direction),
            11 => $query->orderBy('fecha_estandarizacion', $direction),
            12 => $query->orderBy('version_actualizada', $direction),
            13 => $query->orderBy('estado_id', $direction),
            default => $query->orderByDesc('fecha_solicitud')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(MtGi11Solicitud $solicitud, bool $canEdit): array
    {
        $estado = $solicitud->estado;

        // Los botones viajan como HTML para que DataTables los pinte igual que Blade.
        $actions = '<div class="table-actions">'.
            $this->actionButton(
                route('calidad.mt-gi-11.matriz.show', $solicitud),
                'Editar',
                '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
            );

        if ($canEdit) {
            $actions .= $this->actionButton(
                route('calidad.mt-gi-11.matriz.nueva-version', $solicitud),
                'Nueva versión',
                '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
            );

            $actions .= '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger js-mt-gi-11-delete" '.
                'data-delete-url="'.e(route('calidad.mt-gi-11.matriz.destroy', $solicitud)).'" '.
                'data-delete-label="'.e($solicitud->consecutivo).'" title="Eliminar" aria-label="Eliminar">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
                '</button>';
        }

        $actions .= '</div>';

        return [
            e(DisplayDate::date($solicitud->fecha_solicitud)),
            e((string) $solicitud->consecutivo),
            e((string) ($solicitud->proceso?->name ?: '—')),
            e((string) ($solicitud->tipoDocumento?->name ?: '—')),
            e((string) $solicitud->nombre_documento),
            e((string) ($solicitud->tipoSolicitud?->name ?: '—')),
            e((string) ($solicitud->codigo_actual ?: '—')),
            e((string) ($solicitud->version_actual ?: '—')),
            e((string) $solicitud->responsable_proceso),
            e((string) ($solicitud->cargoResponsable?->name ?: '—')),
            e((string) ($solicitud->codigo_estandarizacion ?: '—')),
            e(DisplayDate::date($solicitud->fecha_estandarizacion)),
            e($solicitud->version_actualizada !== null ? (string) $solicitud->version_actualizada : '—'),
            '<span class="status-pill status-pill--'.e($estado?->pillClass() ?? 'muted').'">'.e((string) ($estado?->name ?: '—')).'</span>',
            e((string) ($solicitud->creator?->name ?: '—')),
            e((string) $solicitud->adjuntos_count),
            $actions,
        ];
    }

    private function actionButton(string $url, string $label, string $svg): string
    {
        return '<a href="'.e($url).'" class="cursos-catalogo-page__icon-btn" title="'.e($label).'" aria-label="'.e($label).'">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$svg.'</svg>'.
            '</a>';
    }
}
