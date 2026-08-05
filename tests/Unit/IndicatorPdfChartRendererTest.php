<?php

namespace Tests\Unit;

use App\Services\Indicadores\IndicatorPdfChartRenderer;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('gd')]
class IndicatorPdfChartRendererTest extends TestCase
{
    public function test_mixed_bar_line_chart_returns_png_data_uri(): void
    {
        $renderer = new IndicatorPdfChartRenderer;

        $dataUri = $renderer->mixedBarLineDataUri(
            categories: ['Ene', 'Feb', 'Mar'],
            barSeries: [
                ['label' => 'Base', 'data' => [10, 20, 15], 'color' => [47, 111, 217]],
                ['label' => 'Cumplido', 'data' => [5, 12, 8], 'color' => [120, 182, 63]],
            ],
            lineSeries: [
                ['label' => '%', 'data' => [50, 60, 53], 'color' => [209, 47, 47]],
                ['label' => 'Meta', 'data' => [80, 80, 80], 'color' => [68, 68, 68], 'dashed' => true],
            ],
            title: 'Prueba',
        );

        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }

    public function test_pie_chart_returns_png_data_uri(): void
    {
        $renderer = new IndicatorPdfChartRenderer;

        $dataUri = $renderer->pieChartDataUri([
            'title' => 'Trimestre 1',
            'data' => [
                ['name' => 'Tipo A', 'value' => 3],
                ['name' => 'Tipo B', 'value' => 2],
            ],
        ]);

        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }
}
