<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class AcreditacionAcreditadoDatatableService
{
    public function __construct(
        private readonly AcreditacionAcreditadoListService $listService,
        private readonly AcreditacionEstadoCalculator $estadoCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = (int) config('acreditaciones.limits.datatable_max_length', 100);

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

        /** @var Collection<int, AcreditacionAcreditado> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (AcreditacionAcreditado $row): array => $this->formatRow($row, $canEdit))
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
     * @param  Builder<AcreditacionAcreditado>  $query
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
                ->orWhere('cargo', 'like', $like)
                ->orWhere('cargo_apo', 'like', $like)
                ->orWhere('estado', 'like', $like)
                ->orWhere('observaciones', 'like', $like);
        });
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        // Columns: CEDULA, NOMBRE, CARGO, CARGO APO, VIGEN.ACR, ESTADO, OBSERVACIONES, FECHA SOLICITUD [, Acciones]
        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            2 => $query->orderBy('cargo', $direction),
            3 => $query->orderBy('cargo_apo', $direction),
            4 => $query->orderBy('vigencia_acr', $direction)->orderBy('id', $direction),
            5 => $query->orderBy('estado', $direction),
            7 => $query->orderBy('fecha_solicitud', $direction)->orderBy('id', $direction),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(AcreditacionAcreditado $row, bool $canEdit): array
    {
        $estadoLabel = $this->estadoCalculator->estadoLabel((string) $row->estado);
        $estadoClass = match ($row->estado) {
            AcreditacionAcreditado::ESTADO_ACREDITADO => 'status-pill status-pill--success',
            AcreditacionAcreditado::ESTADO_POR_VENCER => 'status-pill status-pill--warning',
            AcreditacionAcreditado::ESTADO_EN_PROCESO => 'status-pill status-pill--info',
            default => 'status-pill status-pill--danger',
        };

        $cells = [
            e((string) $row->document_number),
            e((string) $row->full_name),
            e((string) $row->cargo),
            e((string) $row->cargo_apo),
            e(optional($row->vigencia_acr)?->format('Y-m-d') ?: '—'),
            sprintf('<span class="%s">%s</span>', e($estadoClass), e($estadoLabel)),
            e(Str::limit((string) ($row->observaciones ?? ''), 60) ?: '—'),
            e(optional($row->fecha_solicitud)?->format('Y-m-d') ?: '—'),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($row);
        }

        return $cells;
    }

    private function formatActionsCell(AcreditacionAcreditado $row): string
    {
        $editPayload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'cargo' => $row->cargo,
            'cargo_apo' => $row->cargo_apo,
            'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d') ?? '',
            'fecha_solicitud' => optional($row->fecha_solicitud)?->format('Y-m-d') ?? '',
            'estado' => $row->estado,
            'estado_label' => $this->estadoCalculator->estadoLabel((string) $row->estado),
            'observaciones' => $row->observaciones ?? '',
            'update_url' => route('gestion-humana.acreditaciones.acreditados.update', $row),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<div class="cursos-registros-page__row-actions table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-acreditado-edit" '.
            'data-acreditado-edit="%s" title="Editar" aria-label="Editar">'.
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
            e(route('gestion-humana.acreditaciones.acreditados.destroy', $row)),
            e(json_encode('¿Eliminar este acreditado?', JSON_UNESCAPED_UNICODE)),
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
