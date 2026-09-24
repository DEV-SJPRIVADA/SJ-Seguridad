<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EmployeeCursoDatatableService
{
    public function __construct(
        private readonly EmployeeCursoListService $listService,
    ) {}

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
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->listService->filteredQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request, $canEdit);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, EmployeeCurso> $cursos */
        $cursos = $query->with(['cursoTipo', 'cursoEscuela'])->get();

        $rows = $cursos
            ->map(fn (EmployeeCurso $curso): array => $this->formatRow($curso, $canEdit))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Filas elegibles para marcado masivo bajo el filtro actual (no SOLICITADO).
     *
     * @param  array<string, mixed>  $filters
     * @return list<array{
     *     id: int,
     *     document_number: string,
     *     full_name: string,
     *     tipo_curso: string,
     *     numero_curso: string,
     *     estado: string,
     *     vigencia: string
     * }>
     */
    public function bulkSelectableRows(array $filters): array
    {
        return $this->listService
            ->filteredQuery($filters, ordered: false)
            ->with(['cursoTipo:id,tipo_curso'])
            ->where('estado', '!=', EmployeeCurso::ESTADO_SOLICITADO)
            ->orderByDesc('fecha_expedicion')
            ->orderByDesc('id')
            ->get([
                'id',
                'document_number',
                'full_name',
                'curso_tipo_id',
                'numero_curso',
                'estado',
                'fecha_expedicion',
            ])
            ->map(fn (EmployeeCurso $curso): array => [
                'id' => $curso->id,
                'document_number' => $curso->document_number,
                'full_name' => $curso->full_name,
                'tipo_curso' => $curso->cursoTipo?->tipo_curso ?? '—',
                'numero_curso' => $curso->numero_curso,
                'estado' => $curso->estado ?: '—',
                'vigencia' => $curso->computeVigencia(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Builder<EmployeeCurso>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('document_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('numero_curso', 'like', $like)
                ->orWhere('escuela_nombre', 'like', $like)
                ->orWhere('escuela_codigo', 'like', $like)
                ->orWhere('escuela_nit', 'like', $like)
                ->orWhere('estado', 'like', $like)
                ->orWhereHas('cursoTipo', fn (Builder $tipo) => $tipo->where('tipo_curso', 'like', $like));
        });
    }

    /**
     * @param  Builder<EmployeeCurso>  $query
     */
    private function applyOrdering(Builder $query, Request $request, bool $canEdit): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('fecha_expedicion')->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $offset = $canEdit ? 1 : 0;
        $logical = $columnIndex - $offset;

        match ($logical) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            3 => $query->orderBy('escuela_nombre', $direction),
            4 => $query->orderBy('escuela_codigo', $direction),
            5 => $query->orderBy('escuela_nit', $direction),
            6 => $query->orderBy('fecha_expedicion', $direction)->orderBy('id', $direction),
            7 => $query->orderBy('numero_curso', $direction),
            9 => $query->orderBy('estado', $direction),
            default => $query->orderByDesc('fecha_expedicion')->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(EmployeeCurso $curso, bool $canEdit): array
    {
        $vigencia = $curso->computeVigencia();
        $vigenciaClass = match ($vigencia) {
            EmployeeCurso::VIGENCIA_VIGENTE => 'status-pill status-pill--success',
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
            e((string) ($curso->escuela_nombre ?: '—')),
            e((string) ($curso->escuela_codigo ?: '—')),
            e((string) ($curso->escuela_nit ?: '—')),
            e(optional($curso->fecha_expedicion)?->format('Y-m-d') ?: '—'),
            e((string) $curso->numero_curso),
            sprintf('<span class="%s">%s</span>', e($vigenciaClass), e($vigencia)),
            e((string) ($curso->estado ?: '—')),
            e(Str::limit((string) $curso->observaciones, 60) ?: '—'),
            $this->formatDocumentCell($curso, $canEdit),
        ]);

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($curso);
        }

        return $cells;
    }

    private function formatSelectCell(EmployeeCurso $curso): string
    {
        $canSelect = $curso->estado !== EmployeeCurso::ESTADO_SOLICITADO;

        if (! $canSelect) {
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

    private function formatDocumentCell(EmployeeCurso $curso, bool $canEdit): string
    {
        $parts = ['<div class="cursos-registros-page__document-cell">'];

        if ($curso->hasDocument()) {
            $parts[] = '<div class="cursos-registros-page__document-links cursos-catalogo-page__row-actions">';
            $parts[] = sprintf(
                '<a class="cursos-catalogo-page__icon-btn" href="%s" title="Descargar" aria-label="Descargar">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/>'.
                '<line x1="12" x2="12" y1="15" y2="3"/></svg></a>',
                e(route('gestion-humana.cursos.registros.document.download', $curso)),
            );

            if ($canEdit) {
                $parts[] = sprintf(
                    '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                    '%s<input type="hidden" name="_method" value="DELETE">'.
                    '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                    'title="Quitar documento" aria-label="Quitar documento">'.
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                    'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                    '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'.
                    '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
                    '</button></form>',
                    e(route('gestion-humana.cursos.registros.document.destroy', $curso)),
                    e(json_encode('¿Quitar el documento?', JSON_UNESCAPED_UNICODE)),
                    $this->csrfField(),
                );
            }

            $parts[] = '</div>';
        } else {
            $parts[] = '<span class="panel-text">Sin archivo</span>';
        }

        if ($canEdit) {
            $inputId = 'curso-document-'.$curso->id;
            $parts[] = sprintf(
                '<form method="POST" action="%s" enctype="multipart/form-data" class="cursos-registros-page__upload" data-curso-upload>'.
                '%s'.
                '<input id="%s" name="document" type="file" class="cursos-registros-page__file-input" '.
                'accept=".pdf,.jpg,.jpeg,.png,.webp" required>'.
                '<span class="cursos-registros-page__file-name cursos-registros-page__file-name--compact" data-curso-upload-name>Sin archivo</span>'.
                '<div class="cursos-registros-page__file-actions cursos-catalogo-page__row-actions">'.
                '<label for="%s" class="cursos-catalogo-page__icon-btn" title="Elegir archivo" aria-label="Elegir archivo">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>'.
                '<path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 12v6"/><path d="m15 15-3-3-3 3"/></svg>'.
                '</label>'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" '.
                'title="Subir" aria-label="Subir">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/>'.
                '<line x1="12" x2="12" y1="3" y2="15"/></svg>'.
                '</button>'.
                '</div></form>',
                e(route('gestion-humana.cursos.registros.document.upload', $curso)),
                $this->csrfField(),
                e($inputId),
                e($inputId),
            );
        }

        $parts[] = '</div>';

        return implode('', $parts);
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
            '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
            '%s<input type="hidden" name="_method" value="DELETE">'.
            '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
            'title="Eliminar" aria-label="Eliminar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'.
            '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
            '</button></form></div>',
            $editPayload,
            e(route('gestion-humana.cursos.registros.destroy', $curso)),
            e(json_encode('¿Eliminar este registro y su documento?', JSON_UNESCAPED_UNICODE)),
            $this->csrfField(),
        );
    }

    private function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s" autocomplete="off">',
            e(csrf_token()),
        );
    }
}
