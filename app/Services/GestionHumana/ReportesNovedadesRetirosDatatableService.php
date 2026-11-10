<?php

namespace App\Services\GestionHumana;

use App\Models\ReportesNovedadesRetiro;
use App\Support\DisplayDate;
use App\Support\ReportesNovedadesPeriodFilter;
use App\Support\ReportesNovedadesUi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ReportesNovedadesRetirosDatatableService
{
    /**
     * @param  array{q: string, fecha_desde: string, fecha_hasta: string}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit, bool $canReview): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = ReportesNovedadesRetiro::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = $this->filteredQuery($filters, $request);
        $recordsFiltered = (clone $query)->count();
        $duplicateDocuments = ReportesNovedadesUi::duplicateDocumentNumbers(clone $query);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, ReportesNovedadesRetiro> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (ReportesNovedadesRetiro $row): array => $this->formatRow($row, $canEdit, $canReview, $duplicateDocuments))
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
     * @return Builder<ReportesNovedadesRetiro>
     */
    public function filteredQuery(array $filters, ?Request $request = null): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        $query = ReportesNovedadesRetiro::query()
            ->when($q !== '', function (Builder $query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('document_number', 'like', $like)
                        ->orWhere('employee_name', 'like', $like);
                });
            });

        $query = ReportesNovedadesPeriodFilter::applyToQuery($query, $filters, 'fecha_retiro');

        $this->applyOrdering($query, $request);

        return $query;
    }

    /**
     * Orden solicitado por DataTables server-side; los indices remiten a las columnas visibles del Blade.
     *
     * @param  Builder<ReportesNovedadesRetiro>  $query
     */
    private function applyOrdering(Builder $query, ?Request $request): void
    {
        $column = $request?->input('order.0.column');
        $direction = $request?->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        match ($column === null || $column === '' ? -1 : (int) $column) {
            0 => $query->orderBy('document_number', $direction)->orderBy('id', $direction),
            1 => $query->orderBy('employee_name', $direction)->orderBy('id', $direction),
            2 => $query->orderBy('fecha_ingreso', $direction)->orderBy('id', $direction),
            3 => $query->orderBy('tipo', $direction)->orderBy('id', $direction),
            4 => $query->orderBy('cargo', $direction)->orderBy('id', $direction),
            5 => $query->orderBy('destino', $direction)->orderBy('id', $direction),
            6 => $query->orderBy('novedad', $direction)->orderBy('id', $direction),
            7 => $query->orderBy('fecha_retiro', $direction)->orderBy('id', $direction),
            8 => $query->orderBy('motivo_retiro', $direction)->orderBy('id', $direction),
            9 => $query->orderBy('observaciones', $direction)->orderBy('id', $direction),
            10 => $query->orderBy('observacion_nomina', $direction)->orderBy('id', $direction),
            default => $query->orderByDesc('fecha_retiro')->orderByDesc('id'),
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
            [
                'key' => static fn (ReportesNovedadesRetiro $row): string => optional($row->fecha_ingreso)?->format('Y-m-d') ?? '',
                'label' => 'FECHA INGRESO',
            ],
            ['key' => 'tipo', 'label' => 'TIPO'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'destino', 'label' => 'DESTINO'],
            ['key' => 'novedad', 'label' => 'NOVEDAD'],
            [
                'key' => static fn (ReportesNovedadesRetiro $row): string => optional($row->fecha_retiro)?->format('Y-m-d') ?? '',
                'label' => 'FECHA RETIRO',
            ],
            ['key' => 'motivo_retiro', 'label' => 'MOTIVO RETIRO'],
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
            ['key' => 'observacion_nomina', 'label' => 'NOMINA'],
        ];
    }

    /**
     * @param  array<string, true>  $duplicateDocuments
     * @return list<string>
     */
    private function formatRow(ReportesNovedadesRetiro $row, bool $canEdit, bool $canReview, array $duplicateDocuments): array
    {
        $cells = [
            ReportesNovedadesUi::cedulaCell(
                $row->document_number,
                isset($duplicateDocuments[trim((string) $row->document_number)]),
            ),
            e((string) $row->employee_name),
            e(DisplayDate::date($row->fecha_ingreso) ?: '—'),
            e((string) ($row->tipo ?: '—')),
            e((string) ($row->cargo ?: '—')),
            e((string) ($row->destino ?: '—')),
            e((string) $row->novedad),
            e(DisplayDate::date($row->fecha_retiro) ?: '—'),
            e((string) ($row->motivo_retiro ?: '—')),
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

    private function formatActionsCell(ReportesNovedadesRetiro $row, bool $canEdit, bool $canReview): string
    {
        $payload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'employee_name' => $row->employee_name,
            'fecha_ingreso' => optional($row->fecha_ingreso)?->format('Y-m-d'),
            'tipo' => $row->tipo,
            'cargo' => $row->cargo,
            'destino' => $row->destino,
            'novedad' => $row->novedad,
            'fecha_retiro' => optional($row->fecha_retiro)?->format('Y-m-d'),
            'motivo_retiro' => $row->motivo_retiro,
            'observaciones' => $row->observaciones,
            'observacion_nomina' => $row->observacion_nomina,
            'update_url' => route('gestion-humana.reportes-novedades.retiros.update', $row),
            'review_url' => route('gestion-humana.reportes-novedades.retiros.review', $row),
            'historial_url' => route('gestion-humana.reportes-novedades.retiros.historial', ['id' => $row->id]),
            'can_edit' => $canEdit,
            'can_review' => $canReview,
        ], JSON_UNESCAPED_UNICODE));

        $buttons = sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-rn-retiro-edit" '.
            'data-row-edit="%s" title="Editar" aria-label="Editar">%s</button>',
            $payload,
            $this->pencilSvg(),
        );

        $buttons .= sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button>',
            e(route('gestion-humana.reportes-novedades.retiros.historial', ['id' => $row->id])),
            $this->historySvg(),
        );

        if ($canEdit) {
            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                '%s<input type="hidden" name="_method" value="DELETE">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                'title="Eliminar" aria-label="Eliminar">%s</button></form>',
                e(route('gestion-humana.reportes-novedades.retiros.destroy', $row)),
                e(json_encode('¿Anular este registro de retiros?', JSON_UNESCAPED_UNICODE)),
                $this->csrfField(),
                $this->trashSvg(),
            );
        }

        return '<div class="table-actions">'.$buttons.'</div>';
    }

    private function formatHistorialOnlyCell(ReportesNovedadesRetiro $row): string
    {
        return sprintf(
            '<div class="table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button></div>',
            e(route('gestion-humana.reportes-novedades.retiros.historial', ['id' => $row->id])),
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
}
