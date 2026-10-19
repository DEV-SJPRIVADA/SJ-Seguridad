<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
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
     * @param  list<int>  $pinnedIds
     * @param  array<string, string>  $actionQuery
     */
    public function respond(
        Request $request,
        array $filters,
        bool $canEdit,
        array $pinnedIds = [],
        array $actionQuery = [],
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = (int) config('acreditaciones.limits.datatable_max_length', 100);

        $baseQuery = $this->listService->filteredQuery($filters, ordered: false, pinnedIds: $pinnedIds);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyDatatableSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request, $canEdit);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min($maxLength, $length)));
        } else {
            $query->take($maxLength);
        }

        /** @var Collection<int, AcreditacionAcreditado> $rows */
        $rows = $query->get();

        $inProfileDocs = $this->documentNumbersInProfile($rows);
        $duplicateDocs = $this->duplicateDocumentNumbers($rows);

        $data = $rows
            ->map(fn (AcreditacionAcreditado $row): array => $this->formatRow(
                $row,
                $canEdit,
                $inProfileDocs->has((string) $row->document_number),
                $duplicateDocs->has((string) $row->document_number),
                $actionQuery,
            ))
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
     * @param  Collection<int, AcreditacionAcreditado>  $rows
     * @return Collection<string, true>
     */
    private function documentNumbersInProfile(Collection $rows): Collection
    {
        $docs = $rows->pluck('document_number')
            ->filter(fn ($doc): bool => filled($doc))
            ->map(fn ($doc): string => (string) $doc)
            ->unique()
            ->values();

        if ($docs->isEmpty()) {
            return collect();
        }

        return EmployeeFichaProfile::query()
            ->whereIn('document_number', $docs->all())
            ->pluck('document_number')
            ->mapWithKeys(fn ($doc): array => [(string) $doc => true]);
    }

    /**
     * Cédulas de la página actual que tienen más de un registro en acreditacion_acreditados.
     *
     * @param  Collection<int, AcreditacionAcreditado>  $rows
     * @return Collection<string, true>
     */
    private function duplicateDocumentNumbers(Collection $rows): Collection
    {
        $docs = $rows->pluck('document_number')
            ->filter(fn ($doc): bool => filled($doc))
            ->map(fn ($doc): string => (string) $doc)
            ->unique()
            ->values();

        if ($docs->isEmpty()) {
            return collect();
        }

        return AcreditacionAcreditado::query()
            ->select('document_number')
            ->whereIn('document_number', $docs->all())
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('document_number')
            ->mapWithKeys(fn ($doc): array => [(string) $doc => true]);
    }

    /**
     * Filas del filtro actual elegibles para acción masiva.
     *
     * @param  array<string, mixed>  $filters
     * @param  list<int>  $pinnedIds
     * @return list<array{
     *     id: int,
     *     document_number: string,
     *     full_name: string,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     vigencia_acr: string
     * }>
     */
    public function bulkSelectableRows(array $filters, array $pinnedIds = []): array
    {
        return $this->listService
            ->filteredQuery($filters, ordered: false, pinnedIds: $pinnedIds)
            ->orderByDesc('id')
            ->get([
                'id',
                'document_number',
                'full_name',
                'cargo',
                'cargo_apo',
                'estado',
                'vigencia_acr',
            ])
            ->map(fn (AcreditacionAcreditado $row): array => [
                'id' => $row->id,
                'document_number' => (string) $row->document_number,
                'full_name' => (string) $row->full_name,
                'cargo' => (string) $row->cargo,
                'cargo_apo' => (string) $row->cargo_apo,
                'estado' => $this->estadoCalculator->estadoLabel((string) $row->estado),
                'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d') ?: '—',
            ])
            ->values()
            ->all();
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
                ->orWhere('renovacion', 'like', $like)
                ->orWhere('observaciones', 'like', $like);
        });
    }

    /**
     * @param  Builder<AcreditacionAcreditado>  $query
     */
    private function applyOrdering(Builder $query, Request $request, bool $canEdit): void
    {
        if (! $request->has('order.0.column')) {
            $query->orderByDesc('id');

            return;
        }

        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $offset = $canEdit ? 1 : 0;
        $logical = $columnIndex - $offset;

        // Logical: CEDULA, NOMBRE, CARGO, CARGO APO, VIGEN.ACR, ESTADO, RENOVACIONES,
        // FECHA SOLICITUD, OBSERVACIONES, EN PERFIL [, Acciones]
        match ($logical) {
            0 => $query->orderBy('document_number', $direction),
            1 => $query->orderBy('full_name', $direction),
            2 => $query->orderBy('cargo', $direction),
            3 => $query->orderBy('cargo_apo', $direction),
            4 => $query->orderBy('vigencia_acr', $direction)->orderBy('id', $direction),
            5 => $query->orderBy('estado', $direction),
            6 => $query->orderBy('renovacion', $direction),
            7 => $query->orderBy('fecha_solicitud', $direction)->orderBy('id', $direction),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @param  array<string, string>  $actionQuery
     * @return list<string>
     */
    private function formatRow(
        AcreditacionAcreditado $row,
        bool $canEdit,
        bool $inProfile,
        bool $isDuplicateCedula,
        array $actionQuery = [],
    ): array {
        $estadoLabel = $this->estadoCalculator->estadoLabel((string) $row->estado);
        $estadoClass = match ($row->estado) {
            AcreditacionAcreditado::ESTADO_ACREDITADO => 'status-pill status-pill--success',
            AcreditacionAcreditado::ESTADO_POR_VENCER => 'status-pill status-pill--warning',
            AcreditacionAcreditado::ESTADO_EN_PROCESO => 'status-pill status-pill--info',
            default => 'status-pill status-pill--danger',
        };

        $enPerfilLabel = $inProfile ? 'Sí' : 'No';
        $enPerfilClass = $inProfile
            ? 'status-pill status-pill--success'
            : 'status-pill status-pill--muted';

        $cells = [];

        if ($canEdit) {
            $cells[] = $this->formatSelectCell($row);
        }

        $cells = array_merge($cells, [
            $this->formatDocumentNumberCell((string) $row->document_number, $isDuplicateCedula),
            e((string) $row->full_name),
            e((string) $row->cargo),
            e((string) $row->cargo_apo),
            e(optional($row->vigencia_acr)?->format('Y-m-d') ?: '—'),
            sprintf('<span class="%s">%s</span>', e($estadoClass), e($estadoLabel)),
            e($row->renovacionLabel()),
            e(optional($row->fecha_solicitud)?->format('Y-m-d') ?: '—'),
            e(Str::limit((string) ($row->observaciones ?? ''), 60) ?: '—'),
            sprintf('<span class="%s">%s</span>', e($enPerfilClass), e($enPerfilLabel)),
        ]);

        if ($canEdit) {
            $cells[] = $this->formatActionsCell($row, $actionQuery);
        }

        return $cells;
    }

    /**
     * Cédula en rojo cuando hay más de un registro con el mismo documento.
     */
    private function formatDocumentNumberCell(string $documentNumber, bool $isDuplicate): string
    {
        $escaped = e($documentNumber);

        if (! $isDuplicate) {
            return $escaped;
        }

        return sprintf(
            '<span class="gh-cedula-duplicate" title="%s">%s</span>',
            e('Cédula con más de un registro'),
            $escaped,
        );
    }

    private function formatSelectCell(AcreditacionAcreditado $row): string
    {
        $payload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'cargo' => $row->cargo,
            'cargo_apo' => $row->cargo_apo,
            'estado' => $this->estadoCalculator->estadoLabel((string) $row->estado),
            'vigencia_acr' => optional($row->vigencia_acr)?->format('Y-m-d') ?: '—',
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<label class="cursos-registros-page__select-label">'.
            '<input type="checkbox" class="cursos-registros-page__select-checkbox js-acreditado-row-select" '.
            'value="%d" data-acreditado-row="%s" aria-label="Seleccionar cédula %s">'.
            '</label>',
            $row->id,
            $payload,
            e((string) $row->document_number),
        );
    }

    /**
     * @param  array<string, string>  $actionQuery
     */
    private function formatActionsCell(AcreditacionAcreditado $row, array $actionQuery = []): string
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
            'renovacion' => $row->renovacion ?? '',
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
            '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form js-confirm-delete" '.
            'data-confirm-title="%s" data-confirm-message="%s">'.
            '%s<input type="hidden" name="_method" value="DELETE">'.
            '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
            'title="Eliminar" aria-label="Eliminar">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'.
            '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>'.
            '</button></form></div>',
            $editPayload,
            e(route('gestion-humana.acreditaciones.acreditados.destroy', [
                'acreditacionAcreditado' => $row,
                ...$actionQuery,
            ])),
            e('Eliminar acreditado'),
            e('¿Eliminar este acreditado? Esta acción no se puede deshacer.'),
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
