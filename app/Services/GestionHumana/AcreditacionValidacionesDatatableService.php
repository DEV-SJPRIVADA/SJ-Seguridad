<?php

namespace App\Services\GestionHumana;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AcreditacionValidacionesDatatableService
{
    public function __construct(
        private readonly AcreditacionValidacionesResultStore $resultStore,
        private readonly AcreditacionValidacionesRowFilter $rowFilter,
    ) {}

    public function respond(
        Request $request,
        int $userId,
        string $fechaReporte,
        string $runToken,
        string $cola,
        bool $canOpenFicha = false,
        bool $canEdit = false,
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $payload = $this->resultStore->get($userId, $fechaReporte, $runToken);

        if ($payload === null) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Corrida expirada o no encontrada. Vuelva a ejecutar validaciones.',
                'expired' => true,
            ]);
        }

        if (! $this->resultStore->isValidCola($cola)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Cola de validación no válida.',
            ], 422);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $payload['colas'][$cola] ?? [];
        $collection = collect($rows);
        $recordsTotal = $collection->count();

        $filtered = $this->rowFilter->apply($collection, $request, $cola);
        $filtered = $this->applySearch($filtered, $request);
        $recordsFiltered = $filtered->count();

        $ordered = $this->applyOrdering($filtered, $request, $cola, $canEdit);

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $maxLength = (int) config('acreditaciones.limits.datatable_max_length', 100);

        if ($length === -1 || $length < 1) {
            $length = $maxLength;
        }

        $page = $ordered
            ->slice($start, min($maxLength, $length))
            ->values()
            ->map(fn (array $row): array => $this->formatRow($row, $cola, $canOpenFicha, $canEdit))
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $page,
            'expired' => false,
        ]);
    }

    /**
     * IDs de acreditados del filtro actual (todas las páginas) para cargar en Export Apo.
     *
     * @return list<array{
     *     id: int,
     *     document_number: string,
     *     full_name: string,
     *     cargo_apo: string,
     *     estado: string
     * }>
     */
    public function bulkSelectableRows(
        Request $request,
        int $userId,
        string $fechaReporte,
        string $runToken,
        string $cola,
    ): array {
        $payload = $this->resultStore->get($userId, $fechaReporte, $runToken);

        if ($payload === null || ! $this->resultStore->isValidCola($cola)) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $payload['colas'][$cola] ?? [];
        $filtered = $this->rowFilter->apply(collect($rows), $request, $cola);
        $filtered = $this->applySearch($filtered, $request);

        $maxIds = (int) config('acreditaciones.limits.bulk_max_ids', 500);

        return $filtered
            ->filter(fn (array $row): bool => (int) ($row['acreditado_id'] ?? 0) > 0)
            ->take($maxIds)
            ->map(fn (array $row): array => [
                'id' => (int) $row['acreditado_id'],
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
                'estado' => (string) ($row['estado_label'] ?? $row['estado'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function applySearch(Collection $rows, Request $request): Collection
    {
        $search = trim($request->string('search.value')->toString());
        if ($search === '') {
            return $rows;
        }

        $needle = mb_strtolower($search, 'UTF-8');

        return $rows->filter(function (array $row) use ($needle): bool {
            foreach ($row as $value) {
                if ($value === null || is_array($value)) {
                    continue;
                }
                if (str_contains(mb_strtolower((string) $value, 'UTF-8'), $needle)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function applyOrdering(Collection $rows, Request $request, string $cola, bool $canEdit): Collection
    {
        $orderColumnIndex = (int) $request->input('order.0.column', $canEdit ? 1 : 0);
        if ($canEdit) {
            $orderColumnIndex = max(0, $orderColumnIndex - 1);
        }

        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $columns = $this->sortableColumns($cola);
        $field = $columns[$orderColumnIndex] ?? $columns[0] ?? 'document_number';

        $sorted = $rows->sortBy(
            fn (array $row): string => mb_strtolower((string) ($row[$field] ?? ''), 'UTF-8'),
            SORT_NATURAL,
            $orderDir === 'desc',
        );

        return $sorted->values();
    }

    /**
     * @return list<string>
     */
    private function sortableColumns(string $cola): array
    {
        return match ($cola) {
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => [
                'document_number',
                'full_name',
                'cargo',
                'personal_tipo',
            ],
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [
                'document_number',
                'full_name',
                'cargo',
                'cargo_apo',
                'estado',
            ],
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [
                'document_number',
                'full_name',
                'cargo_apo',
                'estado',
                'fecha_solicitud',
                'vigencia_apo',
            ],
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => [
                'document_number',
                'full_name',
                'cargo_apo',
                'estado',
                'vigencia_acr',
            ],
            default => ['document_number'],
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row, string $cola, bool $canOpenFicha, bool $canEdit): array
    {
        $dash = '—';
        $actions = $this->formatActionsCell($row, $cola, $canOpenFicha);

        $base = match ($cola) {
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => [
                'document_number' => e((string) ($row['document_number'] ?? '')),
                'full_name' => e((string) ($row['full_name'] ?? '')),
                'cargo' => e((string) ($row['cargo'] ?? '') ?: $dash),
                'personal_tipo' => e((string) ($row['personal_tipo'] ?? '') ?: $dash),
                'actions' => $actions,
            ],
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [
                'document_number' => e((string) ($row['document_number'] ?? '')),
                'full_name' => e((string) ($row['full_name'] ?? '')),
                'cargo' => e((string) ($row['cargo'] ?? '')),
                'cargo_apo' => e((string) ($row['cargo_apo'] ?? '')),
                'estado' => e((string) ($row['estado_label'] ?? $row['estado'] ?? '')),
                'actions' => $actions,
            ],
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [
                'document_number' => e((string) ($row['document_number'] ?? '')),
                'full_name' => e((string) ($row['full_name'] ?? '')),
                'cargo_apo' => e((string) ($row['cargo_apo'] ?? '')),
                'estado' => e((string) ($row['estado_label'] ?? $row['estado'] ?? '')),
                'fecha_solicitud' => e((string) ($row['fecha_solicitud'] ?? $dash)),
                'vigencia_apo' => e((string) ($row['vigencia_apo'] ?? $dash)),
                'actions' => $actions,
            ],
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => [
                'document_number' => e((string) ($row['document_number'] ?? '')),
                'full_name' => e((string) ($row['full_name'] ?? '')),
                'cargo_apo' => e((string) ($row['cargo_apo'] ?? '')),
                'estado' => e((string) ($row['estado_label'] ?? $row['estado'] ?? '')),
                'vigencia_acr' => e((string) ($row['vigencia_acr'] ?? $dash)),
                'actions' => $actions,
            ],
            default => $row,
        };

        if ($canEdit) {
            return array_merge(['select' => $this->formatSelectCell($row)], $base);
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatSelectCell(array $row): string
    {
        $acreditadoId = (int) ($row['acreditado_id'] ?? 0);

        if ($acreditadoId <= 0) {
            return '<span class="cursos-registros-page__select-label" title="Sin registro en Acreditados">—</span>';
        }

        $payload = e(json_encode([
            'id' => $acreditadoId,
            'document_number' => (string) ($row['document_number'] ?? ''),
            'full_name' => (string) ($row['full_name'] ?? ''),
            'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
            'estado' => (string) ($row['estado_label'] ?? $row['estado'] ?? ''),
        ], JSON_UNESCAPED_UNICODE));

        return sprintf(
            '<label class="cursos-registros-page__select-label">'.
            '<input type="checkbox" class="cursos-registros-page__select-checkbox js-validaciones-row-select" '.
            'value="%d" data-validaciones-row="%s" aria-label="Seleccionar cédula %s">'.
            '</label>',
            $acreditadoId,
            $payload,
            e((string) ($row['document_number'] ?? '')),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatActionsCell(array $row, string $cola, bool $canOpenFicha): string
    {
        $buttons = '';

        $fichaEntryId = isset($row['ficha_entry_id']) ? (int) $row['ficha_entry_id'] : 0;
        if ($canOpenFicha && $fichaEntryId > 0) {
            $href = e(route('gestion-humana.ficha-empleados.employees.ficha.edit', $fichaEntryId));
            $buttons .= sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" '.
                'class="cursos-catalogo-page__icon-btn" title="Abrir Ficha" aria-label="Abrir Ficha">%s</a>',
                $href,
                $this->iconExternalLink(),
            );
        }

        if ($cola === AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION) {
            $nuevoPayload = e(json_encode([
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo' => (string) ($row['cargo'] ?? ''),
            ], JSON_UNESCAPED_UNICODE));

            $buttons .= sprintf(
                '<button type="button" class="cursos-catalogo-page__icon-btn js-validaciones-nuevo" '.
                'data-validaciones-nuevo="%s" title="Nuevo acreditado" aria-label="Nuevo acreditado">%s</button>',
                $nuevoPayload,
                $this->iconPlus(),
            );
        } else {
            $acreditadoId = (int) ($row['acreditado_id'] ?? 0);
            if ($acreditadoId > 0) {
                $editPayload = e(json_encode([
                    'id' => $acreditadoId,
                    'document_number' => (string) ($row['document_number'] ?? ''),
                    'full_name' => (string) ($row['full_name'] ?? ''),
                    'cargo' => (string) ($row['cargo'] ?? ''),
                    'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
                    'vigencia_acr' => (string) ($row['vigencia_acr'] ?? ''),
                    'fecha_solicitud' => (string) ($row['fecha_solicitud'] ?? ''),
                    'estado' => (string) ($row['estado'] ?? ''),
                    'estado_label' => (string) ($row['estado_label'] ?? $row['estado'] ?? ''),
                    'renovacion' => (string) ($row['renovacion'] ?? ''),
                    'observaciones' => (string) ($row['observaciones'] ?? ''),
                    'update_url' => route(
                        'gestion-humana.acreditaciones.acreditados.update',
                        $acreditadoId,
                    ),
                ], JSON_UNESCAPED_UNICODE));

                $buttons .= sprintf(
                    '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-validaciones-edit" '.
                    'data-validaciones-edit="%s" title="Editar acreditado" aria-label="Editar acreditado">%s</button>',
                    $editPayload,
                    $this->iconPencil(),
                );
            }
        }

        if ($buttons === '') {
            return '—';
        }

        return '<div class="cursos-registros-page__row-actions table-actions">'.$buttons.'</div>';
    }

    private function iconExternalLink(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>'.
            '</svg>';
    }

    private function iconPlus(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M5 12h14"/><path d="M12 5v14"/></svg>';
    }

    private function iconPencil(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
    }
}
