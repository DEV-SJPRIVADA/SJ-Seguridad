<?php

namespace App\Services\GestionHumana;

use App\Models\MtSt04Registro;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class MtSt04DatatableService
{
    public function __construct(
        private readonly MtSt04ListService $listService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = 100;

        $baseQuery = $this->listService->filteredQuery($filters, ordered: false);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min($maxLength, $length)));
        } else {
            $query->take($maxLength);
        }

        /** @var Collection<int, MtSt04Registro> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (MtSt04Registro $row): array => $this->formatRow($row, $canEdit))
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
     * @param  Builder<MtSt04Registro>  $query
     */
    private function applyDatatableSearch(Builder $query, Request $request): void
    {
        $search = trim($request->string('search.value')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('mt_st_04_registros.document_number', 'like', $like)
                ->orWhere('ficha.full_name', 'like', $like)
                ->orWhere('ficha.position_name', 'like', $like)
                ->orWhere('ficha.work_city_name', 'like', $like)
                ->orWhere('ficha.cost_center_name', 'like', $like)
                ->orWhere('mt_st_04_registros.estado_1', 'like', $like)
                ->orWhere('mt_st_04_registros.estado_2', 'like', $like)
                ->orWhere('mt_st_04_registros.arma', 'like', $like)
                ->orWhere('mt_st_04_registros.apto', 'like', $like);
        });
    }

    /**
     * @param  Builder<MtSt04Registro>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('mt_st_04_registros.id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        // Columnas: identidad (0–4), psicofísico (5–10: arma…estado+obs),
        // psicosensométrico (11–13), acciones (14).
        match ($columnIndex) {
            0 => $query->orderBy('mt_st_04_registros.document_number', $direction),
            1 => $query->orderBy('ficha.full_name', $direction),
            2 => $query->orderBy('ficha.position_name', $direction),
            3 => $query->orderBy('ficha.work_city_name', $direction),
            4 => $query->orderBy('ficha.cost_center_name', $direction),
            5 => $query->orderBy('mt_st_04_registros.arma', $direction),
            6 => $query->orderBy('mt_st_04_registros.fecha_examen_1', $direction)->orderBy('mt_st_04_registros.id', $direction),
            7 => $query->orderBy('mt_st_04_registros.fecha_vencimiento_1', $direction)->orderBy('mt_st_04_registros.id', $direction),
            8 => $query->orderBy('mt_st_04_registros.apto', $direction),
            9 => $query->orderBy('mt_st_04_registros.estado_1', $direction),
            10 => $query->orderBy('mt_st_04_registros.observaciones_1', $direction),
            11 => $query->orderBy('mt_st_04_registros.fecha_examen_2', $direction)->orderBy('mt_st_04_registros.id', $direction),
            12 => $query->orderBy('mt_st_04_registros.fecha_vencimiento_2', $direction)->orderBy('mt_st_04_registros.id', $direction),
            13 => $query->orderBy('mt_st_04_registros.estado_2', $direction),
            default => $query->orderByDesc('mt_st_04_registros.id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(MtSt04Registro $row, bool $canEdit): array
    {
        $cells = [
            e((string) $row->document_number),
            e((string) ($row->ficha_full_name ?: '—')),
            e((string) ($row->ficha_position_name ?: '—')),
            e((string) ($row->ficha_work_city_name ?: '—')),
            e((string) ($row->ficha_cost_center_name ?: '—')),
            e((string) ($row->arma ?: '—')),
            e(DisplayDate::date($row->fecha_examen_1)),
            e(DisplayDate::date($row->fecha_vencimiento_1)),
            e((string) ($row->apto ?: '—')),
            $this->formatEstadoBadge($row->estado_1),
            e(Str::limit((string) ($row->observaciones_1 ?? ''), 40) ?: '—'),
            e(DisplayDate::date($row->fecha_examen_2)),
            e(DisplayDate::date($row->fecha_vencimiento_2)),
            $this->formatEstadoBadge($row->estado_2),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($row);
        }

        return $cells;
    }

    private function formatEstadoBadge(?string $estado): string
    {
        if ($estado === null || $estado === '') {
            return '—';
        }

        $class = match ($estado) {
            MtSt04Registro::ESTADO_VIGENTE => 'status-pill status-pill--success',
            MtSt04Registro::ESTADO_VENCERA => 'status-pill status-pill--warning',
            MtSt04Registro::ESTADO_VENCIDO => 'status-pill status-pill--danger',
            MtSt04Registro::ESTADO_NO_APLICA => 'status-pill status-pill--info',
            default => 'status-pill',
        };

        return sprintf('<span class="%s">%s</span>', e($class), e($estado));
    }

    private function formatActionsCell(MtSt04Registro $row): string
    {
        $editPayload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'full_name' => (string) ($row->ficha_full_name ?? ''),
            'cargo' => (string) ($row->ficha_position_name ?? ''),
            'ciudad' => (string) ($row->ficha_work_city_name ?? ''),
            'puesto' => (string) ($row->ficha_cost_center_name ?? ''),
            'arma' => $row->arma ?? '',
            'fecha_examen_1' => optional($row->fecha_examen_1)?->format('Y-m-d') ?? '',
            'fecha_vencimiento_1' => optional($row->fecha_vencimiento_1)?->format('Y-m-d') ?? '',
            'apto' => $row->apto ?? '',
            'observaciones_1' => $row->observaciones_1 ?? '',
            'estado_1' => $row->estado_1 ?? '',
            'fecha_examen_2' => optional($row->fecha_examen_2)?->format('Y-m-d') ?? '',
            'fecha_vencimiento_2' => optional($row->fecha_vencimiento_2)?->format('Y-m-d') ?? '',
            'observaciones_2' => $row->observaciones_2 ?? '',
            'estado_2' => $row->estado_2 ?? '',
            'update_url' => route('gestion-humana.mt-st-04.matriz.update', $row),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="cursos-registros-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-mt-st-04-edit" '.
            'data-mt-st-04-edit="%s" title="Editar" aria-label="Editar">'.
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
            e(route('gestion-humana.mt-st-04.matriz.destroy', $row)),
            e(json_encode('¿Eliminar este registro de la matriz?', JSON_UNESCAPED_UNICODE)),
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
