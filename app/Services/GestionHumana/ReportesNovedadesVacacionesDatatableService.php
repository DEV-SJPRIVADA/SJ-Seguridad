<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\ReportesNovedadesVacacion;
use App\Support\DisplayDate;
use App\Support\ReportesNovedadesPeriodFilter;
use App\Support\ReportesNovedadesUi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ReportesNovedadesVacacionesDatatableService
{
    /**
     * @param  array{q: string, fecha_desde: string, fecha_hasta: string}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit, bool $canReview): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = ReportesNovedadesVacacion::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = $this->filteredQuery($filters, $request);
        $recordsFiltered = (clone $query)->count();
        $duplicateDocuments = ReportesNovedadesUi::duplicateDocumentNumbers(clone $query);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, ReportesNovedadesVacacion> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (ReportesNovedadesVacacion $row): array => $this->formatRow($row, $canEdit, $canReview, $duplicateDocuments))
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
     * @param  array{q: string, fecha_desde: string, fecha_hasta: string}  $filters
     * @return Builder<ReportesNovedadesVacacion>
     */
    public function filteredQuery(array $filters, ?Request $request = null): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        $query = ReportesNovedadesVacacion::query()
            ->when($q !== '', function (Builder $query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('document_number', 'like', $like)
                        ->orWhere('employee_name', 'like', $like);
                });
            });

        $query = ReportesNovedadesPeriodFilter::applyToQuery($query, $filters, 'fecha_inicio');

        $this->applyOrdering($query, $request);

        return $query;
    }

    /**
     * Orden solicitado por DataTables server-side; los indices remiten a las columnas visibles del Blade.
     *
     * @param  Builder<ReportesNovedadesVacacion>  $query
     */
    private function applyOrdering(Builder $query, ?Request $request): void
    {
        $column = $request?->input('order.0.column');
        $direction = $request?->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        match ($column === null || $column === '' ? -1 : (int) $column) {
            0 => $query->orderBy('document_number', $direction)->orderBy('id', $direction),
            1 => $query->orderBy('employee_name', $direction)->orderBy('id', $direction),
            2 => $query->orderBy('cargo', $direction)->orderBy('id', $direction),
            3 => $query->orderBy('destino', $direction)->orderBy('id', $direction),
            4 => $query->orderBy('novedad', $direction)->orderBy('id', $direction),
            5 => $query->orderBy('dias_novedad', $direction)->orderBy('id', $direction),
            6 => $query->orderBy('fecha_inicio', $direction)->orderBy('id', $direction),
            7 => $query->orderBy('fecha_fin', $direction)->orderBy('id', $direction),
            8 => $query->orderBy('observaciones', $direction)->orderBy('id', $direction),
            9 => $query->orderBy('observacion_nomina', $direction)->orderBy('id', $direction),
            default => $query->orderByDesc('fecha_inicio')->orderByDesc('id'),
        };
    }

    /**
     * @return list<array{key: string|\Closure, label: string}>
     */
    public function exportColumns(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'CEDULA'],
            ['key' => 'employee_name', 'label' => 'NOMBRE'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'destino', 'label' => 'DESTINO'],
            ['key' => 'novedad', 'label' => 'NOVEDAD'],
            ['key' => 'dias_novedad', 'label' => 'DIAS'],
            [
                'key' => static fn (ReportesNovedadesVacacion $row): string => optional($row->fecha_inicio)?->format('Y-m-d') ?? '',
                'label' => 'FECHA INICIO',
            ],
            [
                'key' => static fn (ReportesNovedadesVacacion $row): string => optional($row->fecha_fin)?->format('Y-m-d') ?? '',
                'label' => 'FECHA FIN',
            ],
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
            ['key' => 'observacion_nomina', 'label' => 'OBSERVACION NOMINA'],
        ];
    }

    /**
     * @param  array<string, true>  $duplicateDocuments
     * @return list<string>
     */
    private function formatRow(ReportesNovedadesVacacion $row, bool $canEdit, bool $canReview, array $duplicateDocuments): array
    {
        $cells = [
            ReportesNovedadesUi::cedulaCell(
                $row->document_number,
                isset($duplicateDocuments[trim((string) $row->document_number)]),
            ),
            e((string) $row->employee_name),
            e((string) ($row->cargo ?: '—')),
            e((string) ($row->destino ?: '—')),
            e((string) $row->novedad),
            e((string) $row->dias_novedad),
            e(DisplayDate::date($row->fecha_inicio)),
            e(DisplayDate::date($row->fecha_fin)),
            e((string) ($row->observaciones ?: '—')),
            ReportesNovedadesUi::observacionNominaCell($row->observacion_nomina),
        ];

        if ($canEdit || $canReview) {
            $cells[] = $this->formatActionsCell($row, $canEdit, $canReview);
        } else {
            $cells[] = $this->formatHistorialOnlyCell($row);
        }

        return $cells;
    }

    private function formatActionsCell(ReportesNovedadesVacacion $row, bool $canEdit, bool $canReview): string
    {
        $payload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'employee_name' => $row->employee_name,
            'cargo' => $row->cargo,
            'destino' => $row->destino,
            'novedad' => $row->novedad,
            'dias_novedad' => $row->dias_novedad,
            'fecha_inicio' => optional($row->fecha_inicio)?->format('Y-m-d'),
            'fecha_fin' => optional($row->fecha_fin)?->format('Y-m-d'),
            'fecha_reintegro' => optional($row->fecha_reintegro)?->format('Y-m-d'),
            'periodos' => $row->periodos,
            'signatory_id' => $row->signatory_id,
            'signatory_label' => $this->signatoryLabel($row->signatory_id),
            'observaciones' => $row->observaciones,
            'download_carta_url' => route('gestion-humana.reportes-novedades.vacaciones.carta-word', $row),
            'observacion_nomina' => $row->observacion_nomina,
            'update_url' => route('gestion-humana.reportes-novedades.vacaciones.update', $row),
            'review_url' => route('gestion-humana.reportes-novedades.vacaciones.review', $row),
            'historial_url' => route('gestion-humana.reportes-novedades.vacaciones.historial', ['id' => $row->id]),
            'can_edit' => $canEdit,
            'can_review' => $canReview,
        ], JSON_UNESCAPED_UNICODE));

        $buttons = sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-rn-vacacion-edit" '.
            'data-row-edit="%s" title="Editar" aria-label="Editar">%s</button>',
            $payload,
            $this->pencilSvg(),
        );

        $buttons .= sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button>',
            e(route('gestion-humana.reportes-novedades.vacaciones.historial', ['id' => $row->id])),
            $this->historySvg(),
        );

        if ($canEdit) {
            $buttons .= sprintf(
                '<a href="%s" class="cursos-catalogo-page__icon-btn" title="Descargar carta Word" aria-label="Descargar carta Word">%s</a>',
                e(route('gestion-humana.reportes-novedades.vacaciones.carta-word', $row)),
                $this->downloadSvg(),
            );

            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                '%s<input type="hidden" name="_method" value="DELETE">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                'title="Eliminar" aria-label="Eliminar">%s</button></form>',
                e(route('gestion-humana.reportes-novedades.vacaciones.destroy', $row)),
                e(json_encode('¿Eliminar este registro de vacaciones?', JSON_UNESCAPED_UNICODE)),
                $this->csrfField(),
                $this->trashSvg(),
            );
        }

        return '<div class="table-actions">'.$buttons.'</div>';
    }

    private function formatHistorialOnlyCell(ReportesNovedadesVacacion $row): string
    {
        return sprintf(
            '<div class="table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button></div>',
            e(route('gestion-humana.reportes-novedades.vacaciones.historial', ['id' => $row->id])),
            $this->historySvg(),
        );
    }

    private function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_token" value="%s" autocomplete="off">',
            e(csrf_token()),
        );
    }

    private function pencilSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
    }

    private function trashSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'.
            '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
    }

    private function historySvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>'.
            '<path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>';
    }

    private function downloadSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>'.
            '<polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>';
    }

    private function signatoryLabel(?int $signatoryId): string
    {
        if ($signatoryId === null) {
            return '';
        }

        static $cache = [];

        if (! array_key_exists($signatoryId, $cache)) {
            $item = PayrollCatalogItem::query()->find($signatoryId);
            $cache[$signatoryId] = $item !== null ? $item->name.' — '.$item->code : '';
        }

        return $cache[$signatoryId];
    }
}
