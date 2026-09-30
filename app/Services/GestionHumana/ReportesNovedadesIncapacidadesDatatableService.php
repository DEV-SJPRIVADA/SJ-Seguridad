<?php

namespace App\Services\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Support\DisplayDate;
use App\Support\ReportesNovedadesPeriodFilter;
use App\Support\ReportesNovedadesUi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ReportesNovedadesIncapacidadesDatatableService
{
    /**
     * @param  array{q: string, fecha_desde: string, fecha_hasta: string}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit, bool $canReview): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = ReportesNovedadesIncapacidad::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = $this->filteredQuery($filters);
        $recordsFiltered = (clone $query)->count();

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, ReportesNovedadesIncapacidad> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (ReportesNovedadesIncapacidad $row): array => $this->formatRow($row, $canEdit, $canReview))
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
     * @return Builder<ReportesNovedadesIncapacidad>
     */
    public function filteredQuery(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        $query = ReportesNovedadesIncapacidad::query()
            ->when($q !== '', function (Builder $query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('document_number', 'like', $like)
                        ->orWhere('employee_name', 'like', $like);
                });
            });

        return ReportesNovedadesPeriodFilter::applyToQuery($query, $filters, 'fecha_inicio')
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id');
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
            ['key' => 'tipo_incapacidad', 'label' => 'TIPO INCAPACIDAD'],
            ['key' => 'dias', 'label' => 'DIAS'],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_inicio)?->format('Y-m-d') ?? '',
                'label' => 'FECHA INICIO',
            ],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_fin)?->format('Y-m-d') ?? '',
                'label' => 'FECHA FIN',
            ],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_recepcion)?->format('Y-m-d') ?? '',
                'label' => 'FECHA RECEPCION',
            ],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_devolucion)?->format('Y-m-d') ?? '',
                'label' => 'FECHA DEVOLUCION',
            ],
            ['key' => 'observacion_devolucion', 'label' => 'OBS DEVOLUCION'],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_registro_control_roll)?->format('Y-m-d') ?? '',
                'label' => 'FECHA REG CONTROL ROLL',
            ],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => optional($row->fecha_envio_final)?->format('Y-m-d') ?? '',
                'label' => 'FECHA ENVIO FINAL',
            ],
            ['key' => 'novedad_control_roll', 'label' => 'NOVEDAD CONTROL ROLL'],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => $row->extemporanea ? 'SI' : 'NO',
                'label' => 'EXTEMPORANEA',
            ],
            ['key' => 'observaciones', 'label' => 'OBSERVACIONES'],
            [
                'key' => static fn (ReportesNovedadesIncapacidad $row): string => (string) ($row->diasEntregaCalculados() ?? ''),
                'label' => 'DIAS ENTREGA',
            ],
            ['key' => 'observacion_nomina', 'label' => 'OBSERVACION NOMINA'],
        ];
    }

    /**
     * @return list<string>
     */
    private function formatRow(ReportesNovedadesIncapacidad $row, bool $canEdit, bool $canReview): array
    {
        $cells = [
            e((string) $row->document_number),
            e((string) $row->employee_name),
            e((string) ($row->cargo ?: '—')),
            e((string) ($row->destino ?: '—')),
            e((string) $row->tipo_incapacidad),
            e((string) $row->dias),
            e(DisplayDate::date($row->fecha_inicio)),
            e(DisplayDate::date($row->fecha_fin)),
            e(DisplayDate::date($row->fecha_recepcion) ?: '—'),
            e((string) ($row->diasEntregaCalculados() ?? '—')),
            ReportesNovedadesUi::observacionNominaCell($row->observacion_nomina),
        ];

        if ($canEdit || $canReview) {
            $cells[] = $this->formatActionsCell($row, $canEdit, $canReview);
        } else {
            $cells[] = $this->formatHistorialOnlyCell($row);
        }

        return $cells;
    }

    private function formatActionsCell(ReportesNovedadesIncapacidad $row, bool $canEdit, bool $canReview): string
    {
        $payload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'employee_name' => $row->employee_name,
            'cargo' => $row->cargo,
            'destino' => $row->destino,
            'tipo_incapacidad' => $row->tipo_incapacidad,
            'dias' => $row->dias,
            'fecha_inicio' => optional($row->fecha_inicio)?->format('Y-m-d'),
            'fecha_fin' => optional($row->fecha_fin)?->format('Y-m-d'),
            'fecha_recepcion' => optional($row->fecha_recepcion)?->format('Y-m-d'),
            'fecha_devolucion' => optional($row->fecha_devolucion)?->format('Y-m-d'),
            'observacion_devolucion' => $row->observacion_devolucion,
            'fecha_registro_control_roll' => optional($row->fecha_registro_control_roll)?->format('Y-m-d'),
            'fecha_envio_final' => optional($row->fecha_envio_final)?->format('Y-m-d'),
            'novedad_control_roll' => $row->novedad_control_roll,
            'extemporanea' => (bool) $row->extemporanea,
            'observaciones' => $row->observaciones,
            'observacion_nomina' => $row->observacion_nomina,
            'dias_entrega' => $row->diasEntregaCalculados(),
            'update_url' => route('gestion-humana.reportes-novedades.incapacidades.update', $row),
            'review_url' => route('gestion-humana.reportes-novedades.incapacidades.review', $row),
            'historial_url' => route('gestion-humana.reportes-novedades.incapacidades.historial', ['id' => $row->id]),
            'can_edit' => $canEdit,
            'can_review' => $canReview,
        ], JSON_UNESCAPED_UNICODE));

        $buttons = sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-rn-incapacidad-edit" '.
            'data-row-edit="%s" title="Editar" aria-label="Editar">%s</button>',
            $payload,
            $this->pencilSvg(),
        );

        $buttons .= sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button>',
            e(route('gestion-humana.reportes-novedades.incapacidades.historial', ['id' => $row->id])),
            $this->historySvg(),
        );

        if ($canEdit) {
            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                '%s<input type="hidden" name="_method" value="DELETE">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                'title="Eliminar" aria-label="Eliminar">%s</button></form>',
                e(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row)),
                e(json_encode('¿Eliminar este registro de incapacidades?', JSON_UNESCAPED_UNICODE)),
                $this->csrfField(),
                $this->trashSvg(),
            );
        }

        return '<div class="table-actions">'.$buttons.'</div>';
    }

    private function formatHistorialOnlyCell(ReportesNovedadesIncapacidad $row): string
    {
        return sprintf(
            '<div class="table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button></div>',
            e(route('gestion-humana.reportes-novedades.incapacidades.historial', ['id' => $row->id])),
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
