<?php

namespace App\Exports;

use App\Models\AcreditacionAcreditado;
use App\Models\ClienteInternoSolicitud;
use App\Models\EmployeeArchiveConsultationItem;
use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Services\GestionHumana\GestionHumanaAreaDashboardService;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel del informe mensual GH: hoja de KPIs + capturas de gráficos y hojas de detalle.
 */
class GestionHumanaAreaDashboardExport
{
    /**
     * @var list<string>
     */
    private array $tempImagePaths = [];

    /**
     * @param  array{anio?: string, mes?: string, ciudad?: string, cargo?: string, cliente?: string}  $filters
     * @param  array<string, string>  $chartImages  data URI PNG/JPEG por clave de gráfico
     */
    public function __construct(
        private readonly GestionHumanaAreaDashboardService $dashboardService,
        private readonly array $filters,
        private readonly array $chartImages = [],
    ) {}

    public function download(): StreamedResponse
    {
        $metrics = $this->dashboardService->metrics($this->filters);
        $normalized = $metrics['filters'];
        $sources = $this->dashboardService->exportSourceQueries($normalized);
        $period = $this->dashboardService->monthName((int) $normalized['mes']).' '.$normalized['anio'];
        $fileName = 'dashboard_gh_'.$normalized['anio'].'-'.str_pad($normalized['mes'], 2, '0', STR_PAD_LEFT).'.xlsx';

        $spreadsheet = new Spreadsheet;
        $informe = $spreadsheet->getActiveSheet();
        $informe->setTitle('Informe');
        $this->writeInformeSheet($informe, $metrics, $period);

        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Personal activo',
            'Personal activo (EOM '.$sources['eom']->toDateString().') — '.$period,
            $this->fichaColumns(),
            $this->collectMapped(
                $sources['personal_activo'],
                fn (EmployeeFichaProfile $row): array => $this->mapFicha($row),
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Ingresos',
            'Ingresos del mes — '.$period,
            $this->fichaColumns(),
            $this->collectMapped(
                $sources['ingresos'],
                fn (EmployeeFichaProfile $row): array => $this->mapFicha($row),
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Desvinculaciones',
            'Desvinculaciones del mes — '.$period,
            $this->fichaColumns(),
            $this->collectMapped(
                $sources['desvinculaciones'],
                fn (EmployeeFichaProfile $row): array => $this->mapFicha($row),
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Acreditaciones',
            'Acreditaciones (ACREDITADO) — '.$period,
            $this->acreditacionColumns(),
            $this->collectMapped(
                $sources['acreditaciones'],
                fn (AcreditacionAcreditado $row): array => $this->mapAcreditacion($row),
                'acreditacion_acreditados.id',
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Psicofísicos',
            'Psicofísicos (VIGENTE) — '.$period,
            $this->psicofisicosColumns(),
            $this->collectMapped(
                $sources['psicofisicos'],
                fn (MtSt04Registro $row): array => $this->mapPsicofisico($row),
                'mt_st_04_registros.id',
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Cliente interno',
            'Cliente interno — '.$period,
            ClienteInternoExport::columnDefinitions(),
            $this->collectMapped(
                $sources['cliente_interno'],
                fn (ClienteInternoSolicitud $row): array => ClienteInternoExport::mapSolicitud($row),
            ),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Formación',
            'Formación (personas del ciclo) — '.$period,
            $this->formacionColumns(),
            collect($this->dashboardService->formacionCicloPeople($normalized)),
        );
        $this->writeDataSheet(
            $spreadsheet->createSheet(),
            'Archivo',
            'Archivo (ítems del historial) — '.$period,
            $this->archivoColumns(),
            $this->collectMapped(
                $sources['archivo'],
                fn (EmployeeArchiveConsultationItem $row): array => $this->mapArchivo($row),
            ),
        );

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            try {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            } finally {
                $this->cleanupTempImages();
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Replica el layout de la página: título, filtros, 10 KPIs en una fila y gráficos 3+4.
     *
     * @param  array<string, mixed>  $metrics
     */
    private function writeInformeSheet(Worksheet $sheet, array $metrics, string $period): void
    {
        $filters = $metrics['filters'];
        $kpis = $metrics['kpis'];

        $sheet->setShowGridlines(false);
        $sheet->setPrintGridlines(false);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A3);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(1);
        $sheet->getPageMargins()->setTop(0.35);
        $sheet->getPageMargins()->setBottom(0.35);
        $sheet->getPageMargins()->setLeft(0.35);
        $sheet->getPageMargins()->setRight(0.35);
        $sheet->getSheetView()->setZoomScale(85);

        $lastCol = Coordinate::stringFromColumnIndex(20);
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', 'Dashboard de Gestión Humana – Informe Mensual');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366');
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $filterLine = implode('   ·   ', [
            'Periodo: '.$period,
            'Ciudad: '.($filters['ciudad'] !== '' ? $filters['ciudad'] : 'Todas'),
            'Cargo: '.($filters['cargo'] !== '' ? $filters['cargo'] : 'Todos'),
            'Cliente: '.($filters['cliente'] !== '' ? $filters['cliente'] : 'Todos'),
        ]);
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->setCellValue('A2', $filterLine);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setARGB('FF334155');
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F0FA');
        $sheet->getStyle('A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('A3:'.$lastCol.'3');
        $sheet->setCellValue('A3', (string) ($metrics['meta']['filters_parciales_nota'] ?? ''));
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getStyle('A3')->getFont()->setSize(8)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $operativoHint = $this->formatPct($kpis['personal_operativo_pct'] ?? 0).' del total';
        $kpiCards = [
            ['Personal Activo', $kpis['personal_activo'] ?? 0, '0F172A', null],
            ['Ingresos del Mes', $kpis['ingresos_mes'] ?? 0, '15803D', null],
            ['Desvinculaciones', $kpis['desvinculaciones_mes'] ?? 0, 'BE123C', null],
            ['Rotación Mensual %', $kpis['rotacion_display'] ?? '—', 'CA8A04', null],
            ['Personal Operativo', $kpis['personal_operativo'] ?? 0, '0369A1', $operativoHint],
            ['Acreditaciones', $kpis['acreditaciones'] ?? 0, '15803D', null],
            ['Psicofísicos', $kpis['psicofisicos'] ?? 0, '0EA5E9', null],
            ['Cliente Interno', $kpis['cliente_interno'] ?? 0, '7C3AED', null],
            ['Formación', $kpis['formacion'] ?? 0, '0D9488', null],
            ['Archivo', $kpis['archivo'] ?? 0, '64748B', null],
        ];

        $sheet->getRowDimension(4)->setRowHeight(36);
        $sheet->getRowDimension(5)->setRowHeight(26);
        $sheet->getRowDimension(6)->setRowHeight(16);

        foreach ($kpiCards as $index => [$label, $value, $color, $hint]) {
            $this->writeKpiCard($sheet, $index, $label, $value, $color, $hint, 4);
        }

        $sheet->getRowDimension(7)->setRowHeight(10);
        for ($row = 9; $row <= 16; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
        $sheet->getRowDimension(17)->setRowHeight(12);

        $topHeight = $this->rowsHeightPx($sheet, 9, 16) - 10;

        $this->writeChartPanel($sheet, 'Evolución de Personal', 'evolucion_personal', 1, 7, 8, 9, 16, $this->spanWidthPx($sheet, 1, 7) - 10, $topHeight);
        $this->writeChartPanel($sheet, 'Ingreso Vs Desvinculaciones', 'ingresos_vs_desvinculaciones', 8, 14, 8, 9, 16, $this->spanWidthPx($sheet, 8, 14) - 10, $topHeight);
        $this->writeChartPanel($sheet, 'Evolución Formación', 'evolucion_formacion', 15, 20, 8, 9, 16, $this->spanWidthPx($sheet, 15, 20) - 10, $topHeight);

        $this->writeEqualBottomCharts($sheet);
    }

    /**
     * Fila inferior: 4 gráficos del mismo tamaño. Las columnas del KPI no miden igual,
     * así que la posición se calcula en píxeles y no por grupos de celdas.
     */
    private function writeEqualBottomCharts(Worksheet $sheet): void
    {
        $charts = [
            ['title' => 'Personal por concepto / área', 'key' => 'personal_por_concepto', 'from' => 'A', 'to' => 'E'],
            ['title' => 'Estado Psicofísicos', 'key' => 'estado_psicofisicos', 'from' => 'F', 'to' => 'J'],
            ['title' => 'Estado Acreditación', 'key' => 'estado_acreditacion', 'from' => 'K', 'to' => 'O'],
            ['title' => 'Consultas Archivo', 'key' => 'consultas_archivo', 'from' => 'P', 'to' => 'T'],
        ];

        $sheet->getRowDimension(18)->setRowHeight(22);
        for ($row = 19; $row <= 31; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        $slot = (int) floor($this->spanWidthPx($sheet, 1, 20) / count($charts));
        $maxWidth = max(40, $slot - 18);
        $maxHeight = max(40, $this->rowsHeightPx($sheet, 19, 31) - 8);

        foreach ($charts as $index => $chart) {
            $range = $chart['from'].'18:'.$chart['to'].'18';
            $sheet->mergeCells($range);
            $sheet->setCellValue($chart['from'].'18', $chart['title']);
            $sheet->getStyle($chart['from'].'18')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');
            $sheet->getStyle($range)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $this->embedChartImage(
                $sheet,
                (string) ($this->chartImages[$chart['key']] ?? ''),
                'A',
                19,
                $maxWidth,
                $maxHeight,
                ($index * $slot) + 8,
            );
        }
    }

    /**
     * Tarjeta KPI (franja de color + etiqueta + valor), una por columna del grid de 10.
     */
    private function writeKpiCard(
        Worksheet $sheet,
        int $index,
        string $label,
        mixed $value,
        string $color,
        ?string $hint,
        int $labelRow,
    ): void {
        $stripIndex = ($index * 2) + 1;
        $valueIndex = $stripIndex + 1;
        $strip = Coordinate::stringFromColumnIndex($stripIndex);
        $col = Coordinate::stringFromColumnIndex($valueIndex);
        $endRow = $labelRow + 2;

        $sheet->getColumnDimension($strip)->setWidth(0.85);
        $sheet->getColumnDimension($col)->setWidth(16);

        $stripRange = $strip.$labelRow.':'.$strip.$endRow;
        $sheet->mergeCells($stripRange);
        $sheet->getStyle($stripRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF'.$color);

        $sheet->setCellValue($col.$labelRow, $label);
        $sheet->getStyle($col.$labelRow)->getFont()->setSize(8)->setBold(true)->getColor()->setARGB('FF64748B');
        $sheet->getStyle($col.$labelRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->setCellValue($col.($labelRow + 1), $value);
        $sheet->getStyle($col.($labelRow + 1))->getFont()->setSize(16)->setBold(true)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle($col.($labelRow + 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($hint !== null && $hint !== '') {
            $sheet->setCellValue($col.($labelRow + 2), $hint);
            $sheet->getStyle($col.($labelRow + 2))->getFont()->setSize(8)->getColor()->setARGB('FF64748B');
            $sheet->getStyle($col.($labelRow + 2))->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $cardRange = $strip.$labelRow.':'.$col.$endRow;
        $sheet->getStyle($cardRange)->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFE2E8F0');
        $sheet->getStyle($col.$labelRow.':'.$col.$endRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFFF');
    }

    /**
     * Panel de gráfico: título centrado (como en la página) e imagen debajo.
     */
    private function writeChartPanel(
        Worksheet $sheet,
        string $title,
        string $imageKey,
        int $startCol,
        int $endCol,
        int $titleRow,
        int $imageRow,
        int $endRow,
        int $maxWidthPx,
        int $maxHeightPx,
    ): void {
        $start = Coordinate::stringFromColumnIndex($startCol);
        $end = Coordinate::stringFromColumnIndex($endCol);
        $range = $start.$titleRow.':'.$end.$endRow;

        $sheet->mergeCells($start.$titleRow.':'.$end.$titleRow);
        $sheet->setCellValue($start.$titleRow, $title);
        $sheet->getRowDimension($titleRow)->setRowHeight(22);
        $sheet->getStyle($start.$titleRow)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle($start.$titleRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle($range)->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFDBE3EF');
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFFF');

        $this->embedChartImage(
            $sheet,
            (string) ($this->chartImages[$imageKey] ?? ''),
            $start,
            $imageRow,
            max(40, $maxWidthPx),
            max(40, $maxHeightPx),
        );
    }

    /**
     * Ancho en píxeles de un tramo de columnas (la imagen no puede salirse de ahí).
     */
    private function spanWidthPx(Worksheet $sheet, int $startCol, int $endCol): int
    {
        $pixels = 0;
        for ($index = $startCol; $index <= $endCol; $index++) {
            $pixels += $this->columnWidthPx($sheet, $index);
        }

        return $pixels;
    }

    private function columnWidthPx(Worksheet $sheet, int $index): int
    {
        $width = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->getWidth();
        if ($width < 0) {
            $width = 8.43;
        }

        return (int) floor(((256 * $width + floor(128 / 7)) / 256) * 7);
    }

    /**
     * Excel solo acepta el desplazamiento dentro de la columna ancla.
     *
     * @return array{0: string, 1: int}
     */
    private function anchorAtPixel(Worksheet $sheet, int $pixelX): array
    {
        $pixelX = max(0, $pixelX);
        $cursor = 0;

        for ($index = 1; $index <= 20; $index++) {
            $width = max(1, $this->columnWidthPx($sheet, $index));
            $next = $cursor + $width;
            if ($pixelX < $next || $index === 20) {
                return [
                    Coordinate::stringFromColumnIndex($index),
                    max(0, min($width - 1, $pixelX - $cursor)),
                ];
            }
            $cursor = $next;
        }

        return ['A', 0];
    }

    /**
     * Alto en píxeles de un tramo de filas.
     */
    private function rowsHeightPx(Worksheet $sheet, int $startRow, int $endRow): int
    {
        $points = 0.0;
        for ($row = $startRow; $row <= $endRow; $row++) {
            $height = $sheet->getRowDimension($row)->getRowHeight();
            $points += $height > 0 ? $height : 15;
        }

        return (int) floor($points * 96 / 72);
    }

    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function writeDataSheet(
        Worksheet $sheet,
        string $sheetTitle,
        string $title,
        array $columns,
        Collection $rows,
    ): void {
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31, 'UTF-8'));
        $export = new BaseExport($rows, $columns, 'unused.xlsx', $title);
        $export->writeTo($sheet);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): array<string, mixed>  $map
     * @return Collection<int, array<string, mixed>>
     */
    private function collectMapped(Builder $query, callable $map, ?string $chunkColumn = null): Collection
    {
        $rows = collect();
        $model = $query->getModel();
        $chunkQuery = clone $query;
        $chunkQuery->reorder();
        $chunkQuery->chunkById(
            500,
            function ($records) use (&$rows, $map): void {
                foreach ($records as $record) {
                    $rows->push($map($record));
                }
            },
            $chunkColumn ?? $model->getQualifiedKeyName(),
            $model->getKeyName(),
        );

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function fichaColumns(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'Cédula'],
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'position_name', 'label' => 'Cargo'],
            ['key' => 'ciudad', 'label' => 'Ciudad'],
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'hire_date', 'label' => 'Fecha de ingreso'],
            ['key' => 'termination_date', 'label' => 'Fecha de retiro'],
            ['key' => 'employment_status', 'label' => 'Estado'],
            ['key' => 'operativo', 'label' => 'Operativo'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapFicha(EmployeeFichaProfile $profile): array
    {
        $operative = in_array(
            mb_strtoupper(trim((string) $profile->position_name), 'UTF-8'),
            GestionHumanaAreaDashboardService::operativePositionsUpper(),
            true,
        );

        return [
            'document_number' => $profile->document_number,
            'full_name' => $profile->full_name,
            'position_name' => $profile->position_name,
            'ciudad' => $profile->displayCityName(),
            'cliente' => $this->displayClient($profile),
            'hire_date' => DisplayDate::date($profile->hire_date),
            'termination_date' => DisplayDate::date($profile->termination_date),
            'employment_status' => $profile->employment_status,
            'operativo' => $operative ? 'SÍ' : 'NO',
        ];
    }

    private function displayClient(EmployeeFichaProfile $profile): string
    {
        $work = trim((string) ($profile->work_center_name ?? ''));

        return $work !== '' ? $work : trim((string) ($profile->cost_center_name ?? ''));
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function acreditacionColumns(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'Cédula'],
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'cargo_apo', 'label' => 'Cargo APO'],
            ['key' => 'vigencia_acr', 'label' => 'Vigencia'],
            ['key' => 'fecha_solicitud', 'label' => 'Fecha de solicitud'],
            ['key' => 'estado', 'label' => 'Estado'],
            ['key' => 'renovacion', 'label' => 'Renovación'],
            ['key' => 'observaciones', 'label' => 'Observaciones'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAcreditacion(AcreditacionAcreditado $row): array
    {
        return [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'cargo' => $row->cargo,
            'cargo_apo' => $row->cargo_apo,
            'vigencia_acr' => DisplayDate::date($row->vigencia_acr),
            'fecha_solicitud' => DisplayDate::date($row->fecha_solicitud),
            'estado' => $row->estado,
            'renovacion' => $row->renovacion,
            'observaciones' => $row->observaciones,
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function psicofisicosColumns(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'Cédula'],
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'ciudad', 'label' => 'Ciudad'],
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'arma', 'label' => 'Arma'],
            ['key' => 'fecha_examen_1', 'label' => 'Fecha de examen'],
            ['key' => 'fecha_vencimiento_1', 'label' => 'Fecha de vencimiento'],
            ['key' => 'apto', 'label' => 'Apto'],
            ['key' => 'estado_1', 'label' => 'Estado'],
            ['key' => 'observaciones_1', 'label' => 'Observaciones'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPsicofisico(MtSt04Registro $row): array
    {
        return [
            'document_number' => $row->document_number,
            'full_name' => (string) ($row->ficha_full_name ?? ''),
            'cargo' => (string) ($row->ficha_position_name ?? ''),
            'ciudad' => (string) ($row->ficha_ciudad ?? ''),
            'cliente' => (string) ($row->ficha_cliente ?? ''),
            'arma' => $row->arma,
            'fecha_examen_1' => DisplayDate::date($row->fecha_examen_1),
            'fecha_vencimiento_1' => DisplayDate::date($row->fecha_vencimiento_1),
            'apto' => $row->apto,
            'estado_1' => $row->estado_1,
            'observaciones_1' => $row->observaciones_1,
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function formacionColumns(): array
    {
        return [
            ['key' => 'numero_id', 'label' => 'Número de ID'],
            ['key' => 'nombre_completo', 'label' => 'Nombre completo'],
            ['key' => 'estado_ciclo', 'label' => 'Estado del ciclo'],
            ['key' => 'cursos', 'label' => 'Cursos en el periodo'],
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function archivoColumns(): array
    {
        return [
            ['key' => 'document_number', 'label' => 'Cédula'],
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'motivos', 'label' => 'Motivos'],
            ['key' => 'concept', 'label' => 'Concepto'],
            ['key' => 'delivered_to', 'label' => 'Entregado a'],
            ['key' => 'received', 'label' => 'Recibido'],
            ['key' => 'observation', 'label' => 'Observación'],
            ['key' => 'week_of_month', 'label' => 'Semana'],
            ['key' => 'month_label', 'label' => 'Mes'],
            ['key' => 'created_at', 'label' => 'Fecha'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapArchivo(EmployeeArchiveConsultationItem $row): array
    {
        return [
            'document_number' => $row->document_number,
            'full_name' => $row->full_name,
            'motivos' => $row->consultation?->conceptLabel() ?? '',
            'concept' => $row->concept,
            'delivered_to' => $row->delivered_to,
            'received' => $row->received ? 'SÍ' : 'NO',
            'observation' => $row->observation,
            'week_of_month' => $row->week_of_month,
            'month_label' => $row->month_label,
            'created_at' => DisplayDate::dateTime($row->created_at),
        ];
    }

    private function embedChartImage(
        Worksheet $sheet,
        string $dataUri,
        string $column,
        int $row,
        int $maxWidthPx,
        int $maxHeightPx,
        ?int $originX = null,
    ): void {
        if ($dataUri === '' || ! str_contains($dataUri, ',')) {
            return;
        }

        $meta = explode(',', $dataUri, 2);
        $header = strtolower($meta[0] ?? '');
        $allowed = str_contains($header, 'image/png')
            || str_contains($header, 'image/jpeg')
            || str_contains($header, 'image/jpg');
        if (! $allowed) {
            return;
        }

        $binary = base64_decode($meta[1] ?? '', true);
        if ($binary === false || $binary === '') {
            return;
        }

        $ext = str_contains($header, 'jpeg') || str_contains($header, 'jpg') ? '.jpg' : '.png';
        $tmp = tempnam(sys_get_temp_dir(), 'ghdash');
        if ($tmp === false) {
            return;
        }

        $path = $tmp.$ext;
        @unlink($tmp);
        if (file_put_contents($path, $binary) === false) {
            return;
        }

        $this->tempImagePaths[] = $path;

        $size = @getimagesize($path);
        $imgW = (int) ($size[0] ?? 0);
        $imgH = (int) ($size[1] ?? 0);
        $displayW = $maxWidthPx;
        $innerOffset = 4;
        if ($imgW > 0 && $imgH > 0) {
            $scale = min($maxWidthPx / $imgW, $maxHeightPx / $imgH);
            $displayW = max(1, (int) round($imgW * $scale));
            $innerOffset = max(2, (int) floor(($maxWidthPx - $displayW) / 2));
        }

        $offsetX = $innerOffset;
        if ($originX !== null) {
            [$column, $offsetX] = $this->anchorAtPixel($sheet, $originX + $innerOffset);
        }

        $drawing = new Drawing;
        $drawing->setName('Gráfico');
        $drawing->setPath($path);
        $drawing->setCoordinates($column.$row);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY(4);
        $drawing->setWidth($displayW);
        $drawing->setWorksheet($sheet);
    }

    private function formatPct(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '—';
        }

        return number_format((float) $value, 1, '.', '').'%';
    }

    private function cleanupTempImages(): void
    {
        foreach ($this->tempImagePaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempImagePaths = [];
    }
}
