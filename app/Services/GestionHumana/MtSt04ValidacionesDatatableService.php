<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class MtSt04ValidacionesDatatableService
{
    public function __construct(
        private readonly MtSt04ValidacionesService $validacionesService,
    ) {}

    /**
     * @param  array{q?: string|null, cola?: string|null, ciudad?: string|null, cargo?: string|null}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = 100;

        $baseQuery = $this->validacionesService->filteredQuery($filters, ordered: false);
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

        /** @var Collection<int, EmployeeFichaProfile> $rows */
        $rows = $query->get([
            'id',
            'document_number',
            'full_name',
            'position_name',
            'work_city_name',
            'residence_city_name',
            'cost_center_name',
            'requires_psicofisicos',
        ]);

        $cola = (string) ($filters['cola'] ?? 'pendientes');

        $data = $rows
            ->map(fn (EmployeeFichaProfile $row): array => $this->formatRow($row, $canEdit, $cola))
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
     * @param  Builder<EmployeeFichaProfile>  $query
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
                ->orWhere('position_name', 'like', $like)
                ->orWhere('work_city_name', 'like', $like)
                ->orWhere('residence_city_name', 'like', $like)
                ->orWhere('cost_center_name', 'like', $like);
        });
    }

    /**
     * @param  Builder<EmployeeFichaProfile>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderBy('full_name')->orderBy('document_number');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'asc') === 'asc' ? 'asc' : 'desc';

        match ($columnIndex) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            2 => $query->orderBy('position_name', $direction),
            3 => $query->orderByRaw(EmployeeFichaProfile::displayCitySql().' '.$direction),
            4 => $query->orderBy('cost_center_name', $direction),
            default => $query->orderBy('full_name')->orderBy('document_number'),
        };
    }

    /**
     * @return list<string>
     */
    private function formatRow(EmployeeFichaProfile $row, bool $canEdit, string $cola): array
    {
        $cells = [
            e((string) $row->document_number),
            e((string) ($row->full_name ?: '—')),
            e((string) ($row->position_name ?: '—')),
            e((string) ($row->displayCityName() ?: '—')),
            e((string) ($row->cost_center_name ?: '—')),
        ];

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($row, $cola);
        }

        return $cells;
    }

    private function formatActionsCell(EmployeeFichaProfile $row, string $cola): string
    {
        $doc = e((string) $row->document_number);

        $addPayload = e(json_encode([
            'document_number' => $row->document_number,
            'full_name' => (string) ($row->full_name ?? ''),
            'cargo' => trim((string) ($row->position_name ?? '')),
            'ciudad' => $row->displayCityName(),
            'puesto' => trim((string) ($row->cost_center_name ?? '')),
        ], JSON_UNESCAPED_UNICODE));

        $buttons = '';

        if ($cola !== 'omitidos') {
            $buttons .= sprintf(
                '<button type="button" class="cursos-catalogo-page__icon-btn js-mt-st-04-validacion-add" '.
                'data-mt-st-04-add="%s" title="Agregar a matriz" aria-label="Agregar a matriz">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M5 12h14"/><path d="M12 5v14"/></svg></button>',
                $addPayload,
            );

            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                '%s'.
                '<input type="hidden" name="document_number" value="%s">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                'title="No requiere psicofísicos" aria-label="No requiere psicofísicos">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M4.929 4.929 19.07 19.071"/><circle cx="12" cy="12" r="10"/></svg>'.
                '</button></form>',
                e(route('gestion-humana.mt-st-04.validaciones.omit')),
                e(json_encode(
                    '¿Marcar a '.$row->document_number.' como que no requiere psicofísicos? Dejará de aparecer en Validaciones.',
                    JSON_UNESCAPED_UNICODE
                )),
                $this->csrfField(),
                $doc,
            );
        } else {
            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form">'.
                '%s'.
                '<input type="hidden" name="document_number" value="%s">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" '.
                'title="Volver a requerir psicofísicos" aria-label="Volver a requerir psicofísicos">'.
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
                'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
                '<path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.76 1.04 6.4 2.72"/><path d="M21 3v6h-6"/></svg>'.
                '</button></form>',
                e(route('gestion-humana.mt-st-04.validaciones.enable')),
                $this->csrfField(),
                $doc,
            );
        }

        return '<div class="cursos-registros-page__row-actions table-actions">'.$buttons.'</div>';
    }

    private function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s" autocomplete="off">',
            e(csrf_token()),
        );
    }
}
