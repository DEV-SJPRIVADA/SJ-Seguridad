<?php

namespace App\Services\Calidad\Planeacion;

use App\Exports\PlaneacionFichaExport;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use App\Services\Indicadores\IndicatorPdfChartRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlaneacionFichaExportService
{
    /**
     * @var list<array{int, int, int}>
     */
    private const BAR_COLORS = [
        [47, 111, 217],
        [120, 182, 63],
        [13, 148, 136],
        [202, 138, 4],
    ];

    public function __construct(
        private readonly PlaneacionAuditLogService $audit,
        private readonly IndicatorPdfChartRenderer $charts,
    ) {}

    /**
     * @param  array<string, mixed>  $ficha
     */
    public function excel(QualityPlanningIndicator $indicator, array $ficha, User $user): StreamedResponse
    {
        $fileName = $this->fileName($indicator, $ficha, 'xlsx');
        $this->audit->logEvent(
            eventType: 'export',
            action: 'ficha_excel',
            reason: 'Exportó la ficha a Excel',
            metadata: $this->metadata($indicator, $ficha),
            model: $indicator,
            userId: $user->id,
        );

        return (new PlaneacionFichaExport)->download($indicator, $ficha, $fileName, $this->chartImage($ficha, 860, 280));
    }

    /**
     * @param  array<string, mixed>  $ficha
     */
    public function pdf(QualityPlanningIndicator $indicator, array $ficha, User $user): Response
    {
        $fileName = $this->fileName($indicator, $ficha, 'pdf');
        $this->audit->logEvent(
            eventType: 'export',
            action: 'ficha_pdf',
            reason: 'Exportó la ficha a PDF',
            metadata: $this->metadata($indicator, $ficha),
            model: $indicator,
            userId: $user->id,
        );

        $logoPath = public_path('images/logo_web.png');
        if (! is_file($logoPath)) {
            $logoPath = public_path('images/logoSj.png');
        }

        return Pdf::loadView('areas.calidad.planeacion.exports.ficha-pdf', [
            'indicator' => $indicator,
            'ficha' => $ficha,
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'chartImage' => $this->chartImage($ficha, 780, 170),
        ])->setPaper('a4', 'landscape')->download($fileName);
    }

    /**
     * Imagen PNG del gráfico (GD). Si no hay datos o falta la extensión, el export sigue sin imagen.
     *
     * @param  array<string, mixed>  $ficha
     */
    private function chartImage(array $ficha, int $width, int $height): ?string
    {
        $payload = is_array($ficha['chart'] ?? null) ? $ficha['chart'] : [];
        $categories = is_array($payload['months'] ?? null) ? $payload['months'] : [];
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];

        if ($categories === [] || $fields === []) {
            return null;
        }

        $barSeries = [];
        foreach (array_values($fields) as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $barSeries[] = [
                'label' => (string) ($field['label'] ?? $field['key'] ?? 'Campo'),
                'data' => is_array($field['data'] ?? null) ? $field['data'] : [],
                'color' => self::BAR_COLORS[$index % count(self::BAR_COLORS)],
            ];
        }

        if ($barSeries === []) {
            return null;
        }

        return $this->charts->mixedBarLineDataUri(
            categories: $categories,
            barSeries: $barSeries,
            lineSeries: [
                [
                    'label' => 'Resultado',
                    'data' => is_array($payload['result'] ?? null) ? $payload['result'] : [],
                    'color' => [209, 47, 47],
                ],
                [
                    'label' => 'Meta',
                    'data' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
                    'color' => [68, 68, 68],
                    'dashed' => true,
                ],
            ],
            title: (string) ($payload['title'] ?? ''),
            rightAxisMax: max(1, (int) ($payload['y_right_max'] ?? 100)),
            width: $width,
            height: $height,
            showDataLabels: true,
        );
    }

    /**
     * @param  array<string, mixed>  $ficha
     */
    private function fileName(QualityPlanningIndicator $indicator, array $ficha, string $extension): string
    {
        return sprintf('ficha-%s-%d.%s', $indicator->code, (int) ($ficha['year'] ?? now()->year), $extension);
    }

    /**
     * @param  array<string, mixed>  $ficha
     * @return array<string, mixed>
     */
    private function metadata(QualityPlanningIndicator $indicator, array $ficha): array
    {
        return [
            'indicator' => $indicator->code,
            'year' => $ficha['year'] ?? null,
            'month' => $ficha['month'] ?? null,
            'capturador_id' => $ficha['capturador_id'] ?? null,
            'version_id' => $ficha['consult_version_id'] ?? null,
        ];
    }
}
