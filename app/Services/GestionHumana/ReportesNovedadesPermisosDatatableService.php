<?php

namespace App\Services\GestionHumana;

use App\Models\ReportesNovedadesPermiso;
use App\Support\DisplayDate;
use App\Support\ReportesNovedadesPeriodFilter;
use App\Support\ReportesNovedadesUi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ReportesNovedadesPermisosDatatableService
{
    /**
     * @param  array{q: string, fecha_desde: string, fecha_hasta: string}  $filters
     */
    public function respond(Request $request, array $filters, bool $canEdit, bool $canReview): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);

        $baseQuery = ReportesNovedadesPermiso::query();
        $recordsTotal = (clone $baseQuery)->count();

        $query = $this->filteredQuery($filters);
        $recordsFiltered = (clone $query)->count();

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        /** @var Collection<int, ReportesNovedadesPermiso> $rows */
        $rows = $query->get();

        $data = $rows
            ->map(fn (ReportesNovedadesPermiso $row): array => $this->formatRow($row, $canEdit, $canReview))
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
     * @return Builder<ReportesNovedadesPermiso>
     */
    public function filteredQuery(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        $query = ReportesNovedadesPermiso::query()
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
            ['key' => 'tipo', 'label' => 'TIPO'],
            ['key' => 'cargo', 'label' => 'CARGO'],
            ['key' => 'novedad', 'label' => 'NOVEDAD'],
            ['key' => 'dias_novedad', 'label' => 'DIAS'],
            [
                'key' => static fn (ReportesNovedadesPermiso $row): string => optional($row->fecha_inicio)?->format('Y-m-d') ?? '',
                'label' => 'FECHA INICIO',
            ],
            [
                'key' => static fn (ReportesNovedadesPermiso $row): string => optional($row->fecha_fin)?->format('Y-m-d') ?? '',
                'label' => 'FECHA FIN',
            ],
            [
                'key' => static fn (ReportesNovedadesPermiso $row): string => $row->marca_gh ? 'Si' : 'No',
                'label' => 'MARCA GH',
            ],
            ['key' => 'observacion_nomina', 'label' => 'OBSERVACION NOMINA'],
        ];
    }

    /**
     * @return list<string>
     */
    private function formatRow(ReportesNovedadesPermiso $row, bool $canEdit, bool $canReview): array
    {
        $cells = [
            e((string) $row->document_number),
            e((string) $row->employee_name),
            e((string) ($row->tipo ?: '—')),
            e((string) ($row->cargo ?: '—')),
            e((string) $row->novedad),
            e((string) $row->dias_novedad),
            e(DisplayDate::date($row->fecha_inicio)),
            e(DisplayDate::date($row->fecha_fin)),
            e($row->marca_gh ? 'Si' : 'No'),
            ReportesNovedadesUi::observacionNominaCell($row->observacion_nomina),
        ];

        if ($canEdit || $canReview) {
            $cells[] = $this->formatActionsCell($row, $canEdit, $canReview);
        } else {
            $cells[] = $this->formatHistorialOnlyCell($row);
        }

        return $cells;
    }

    private function formatActionsCell(ReportesNovedadesPermiso $row, bool $canEdit, bool $canReview): string
    {
        $payload = e(json_encode([
            'id' => $row->id,
            'document_number' => $row->document_number,
            'employee_name' => $row->employee_name,
            'tipo' => $row->tipo,
            'cargo' => $row->cargo,
            'novedad' => $row->novedad,
            'dias_novedad' => $row->dias_novedad,
            'fecha_inicio' => optional($row->fecha_inicio)?->format('Y-m-d'),
            'fecha_fin' => optional($row->fecha_fin)?->format('Y-m-d'),
            'marca_gh' => (bool) $row->marca_gh,
            'observacion_nomina' => $row->observacion_nomina,
            'update_url' => route('gestion-humana.reportes-novedades.permisos.update', $row),
            'review_url' => route('gestion-humana.reportes-novedades.permisos.review', $row),
            'historial_url' => route('gestion-humana.reportes-novedades.permisos.historial', ['id' => $row->id]),
            'can_edit' => $canEdit,
            'can_review' => $canReview,
        ], JSON_UNESCAPED_UNICODE));

        $buttons = sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit js-rn-permiso-edit" '.
            'data-row-edit="%s" title="Editar" aria-label="Editar">%s</button>',
            $payload,
            $this->pencilSvg(),
        );

        $buttons .= sprintf(
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button>',
            e(route('gestion-humana.reportes-novedades.permisos.historial', ['id' => $row->id])),
            $this->historySvg(),
        );

        if ($canEdit) {
            $buttons .= sprintf(
                '<form method="POST" action="%s" class="cursos-catalogo-page__delete-form" onsubmit="return confirm(%s);">'.
                '%s<input type="hidden" name="_method" value="DELETE">'.
                '<button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" '.
                'title="Eliminar" aria-label="Eliminar">%s</button></form>',
                e(route('gestion-humana.reportes-novedades.permisos.destroy', $row)),
                e(json_encode('¿Eliminar este registro de permisos?', JSON_UNESCAPED_UNICODE)),
                $this->csrfField(),
                $this->trashSvg(),
            );
        }

        return '<div class="table-actions">'.$buttons.'</div>';
    }

    private function formatHistorialOnlyCell(ReportesNovedadesPermiso $row): string
    {
        return sprintf(
            '<div class="table-actions">'.
            '<button type="button" class="cursos-catalogo-page__icon-btn js-rn-historial" '.
            'data-historial-url="%s" title="Historial" aria-label="Historial">%s</button></div>',
            e(route('gestion-humana.reportes-novedades.permisos.historial', ['id' => $row->id])),
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
