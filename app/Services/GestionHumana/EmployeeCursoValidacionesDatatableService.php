<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class EmployeeCursoValidacionesDatatableService
{
    public function __construct(
        private readonly EmployeeCursoValidacionesService $validacionesService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, string $cola, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        if ($cola === EmployeeCursoValidacionesService::COLA_SIN_CURSO) {
            return $this->respondSinCurso($request, $filters, $canEdit, $draw, $start, $length);
        }

        return $this->respondPorActualizarVencidos($request, $filters, $canEdit, $draw, $start, $length);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function respondSinCurso(
        Request $request,
        array $filters,
        bool $canEdit,
        int $draw,
        int $start,
        int $length,
    ): JsonResponse {
        $baseQuery = $this->validacionesService->sinCursoQuery($filters);
        $recordsTotal = (clone $baseQuery)->reorder()->count();

        $query = clone $baseQuery;
        $this->applySearchSinCurso($query, $request);
        $recordsFiltered = (clone $query)->reorder()->count();

        $this->applyOrderingSinCurso($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, EmployeeFichaProfile> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (EmployeeFichaProfile $profile): array => $this->formatSinCursoRow($profile, $canEdit))
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
     * @param  array<string, mixed>  $filters
     */
    private function respondPorActualizarVencidos(
        Request $request,
        array $filters,
        bool $canEdit,
        int $draw,
        int $start,
        int $length,
    ): JsonResponse {
        $baseQuery = $this->validacionesService->porActualizarVencidosQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applySearchCursos($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrderingCursos($query, $request, $canEdit);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, EmployeeCurso> $cursos */
        $cursos = $query->with(['cursoTipo', 'cursoEscuela'])->get();

        $data = $cursos
            ->map(fn (EmployeeCurso $curso): array => $this->formatCursoRow($curso, $canEdit))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function applySearchSinCurso(Builder $query, Request $request): void
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('document_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('position_name', 'like', $like);
        });
    }

    private function applyOrderingSinCurso(Builder $query, Request $request): void
    {
        $order = $request->input('order.0');
        $columnIndex = is_array($order) ? (int) ($order['column'] ?? 0) : 0;
        $direction = strtolower((string) (is_array($order) ? ($order['dir'] ?? 'asc') : 'asc')) === 'desc' ? 'desc' : 'asc';

        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            2 => $query->orderBy('position_name', $direction),
            default => $query->orderBy('full_name', $direction)->orderBy('document_number', $direction),
        };
    }

    private function applySearchCursos(Builder $query, Request $request): void
    {
        $search = trim((string) data_get($request->input('search'), 'value', ''));
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('document_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('numero_curso', 'like', $like)
                ->orWhere('estado', 'like', $like)
                ->orWhereHas('cursoTipo', function (Builder $tipo) use ($like): void {
                    $tipo->where('tipo_curso', 'like', $like);
                });
        });
    }

    private function applyOrderingCursos(Builder $query, Request $request, bool $canEdit): void
    {
        $order = $request->input('order.0');
        $columnIndex = is_array($order) ? (int) ($order['column'] ?? 0) : 0;
        $direction = strtolower((string) (is_array($order) ? ($order['dir'] ?? 'desc') : 'desc')) === 'asc' ? 'asc' : 'desc';
        $offset = $canEdit ? 1 : 0;
        $logical = $columnIndex - $offset;

        match ($logical) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            3 => $query->orderBy('numero_curso', $direction),
            4 => $query->orderBy('fecha_expedicion', $direction),
            6 => $query->orderBy('estado', $direction),
            default => $query->orderByDesc('fecha_expedicion')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatSinCursoRow(EmployeeFichaProfile $profile, bool $canEdit): array
    {
        $cells = [
            e((string) $profile->document_number),
            e((string) ($profile->full_name ?: '—')),
            e((string) ($profile->position_name ?: '—')),
        ];

        if ($canEdit) {
            $payload = e(json_encode([
                'document_number' => $profile->document_number,
                'full_name' => $profile->full_name ?: '',
            ], JSON_UNESCAPED_UNICODE));

            $cells[] = sprintf(
                '<div class="cursos-registros-page__row-actions table-actions">'.
                '<button type="button" class="btn btn--primary btn--sm js-cursos-validaciones-nuevo" '.
                'data-cursos-nuevo="%s" title="Agregar curso" aria-label="Agregar curso">Agregar curso</button>'.
                '</div>',
                $payload,
            );
        }

        return $cells;
    }

    /**
     * @return list<string>
     */
    private function formatCursoRow(EmployeeCurso $curso, bool $canEdit): array
    {
        $vigencia = $curso->computeVigencia();
        $vigenciaClass = match ($vigencia) {
            EmployeeCurso::VIGENCIA_ACTUALIZAR => 'status-pill status-pill--warning',
            default => 'status-pill status-pill--danger',
        };

        $cells = [];
        if ($canEdit) {
            $cells[] = $this->formatSelectCell($curso);
        }

        $cells = array_merge($cells, [
            e((string) $curso->document_number),
            e((string) $curso->full_name),
            e((string) ($curso->cursoTipo?->tipo_curso ?: '—')),
            e((string) $curso->numero_curso),
            e(optional($curso->fecha_expedicion)?->format('Y-m-d') ?: '—'),
            sprintf('<span class="%s">%s</span>', e($vigenciaClass), e($vigencia)),
            e((string) ($curso->estado ?: '—')),
        ]);

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($curso);
        }

        return $cells;
    }

    private function formatSelectCell(EmployeeCurso $curso): string
    {
        if ($curso->estado === EmployeeCurso::ESTADO_SOLICITADO) {
            return '<span class="cursos-registros-page__select-disabled" title="Ya está SOLICITADO">—</span>';
        }

        $payload = e(json_encode([
            'id' => $curso->id,
            'document_number' => $curso->document_number,
            'full_name' => $curso->full_name,
            'tipo_curso' => $curso->cursoTipo?->tipo_curso ?? '—',
            'numero_curso' => $curso->numero_curso,
            'estado' => $curso->estado ?: '—',
            'vigencia' => $curso->computeVigencia(),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<label class="cursos-registros-page__select-label">'.
            '<input type="checkbox" class="cursos-registros-page__select-checkbox js-curso-row-select" '.
            'value="%d" data-curso-row="%s" aria-label="Seleccionar curso %s">'.
            '</label>',
            $curso->id,
            $payload,
            e((string) $curso->numero_curso),
        );
    }

    private function formatActionsCell(EmployeeCurso $curso): string
    {
        $editPayload = e(json_encode([
            'id' => $curso->id,
            'document_number' => $curso->document_number,
            'full_name' => $curso->full_name,
            'curso_tipo_id' => (string) $curso->curso_tipo_id,
            'curso_escuela_id' => $curso->curso_escuela_id ? (string) $curso->curso_escuela_id : '',
            'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d'),
            'numero_curso' => $curso->numero_curso,
            'estado' => $curso->estado ?? '',
            'observaciones' => $curso->observaciones ?? '',
            'update_url' => route('gestion-humana.cursos.registros.update', $curso),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="cursos-registros-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-curso-edit" '.
            'data-curso-edit="%s" title="Editar" aria-label="Editar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'.
            '</button>'.
            '</div>',
            $editPayload,
        );
    }
}
