<?php

namespace Tests\Unit;

use App\Services\Indicadores\ManagementReport\ManagementReportChartUpdater;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManagementReportChartUpdaterTest extends TestCase
{
    #[Test]
    public function it_replaces_numeric_series_in_chart_cache(): void
    {
        $xml = <<<'XML'
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
<c:chart><c:plotArea><c:barChart>
<c:ser><c:cat><c:strRef><c:strCache><c:ptCount val="12"/><c:pt idx="0"><c:v>ENE</c:v></c:pt></c:strCache></c:strRef></c:cat>
<c:val><c:numRef><c:numCache><c:ptCount val="12"/><c:pt idx="0"><c:v>1</c:v></c:pt><c:pt idx="1"><c:v>2</c:v></c:pt></c:numCache></c:numRef></c:val></c:ser>
<c:ser><c:cat><c:strRef><c:strCache><c:pt idx="0"><c:v>ENE</c:v></c:pt></c:strCache></c:strRef></c:cat><c:val><c:numRef><c:numCache><c:pt idx="0"><c:v>3</c:v></c:pt></c:numCache></c:numRef></c:val></c:ser>
<c:ser><c:val><c:numRef><c:numCache><c:pt idx="0"><c:v>0.5</c:v></c:pt></c:numCache></c:numRef></c:val></c:ser>
<c:ser><c:val><c:numRef><c:numCache><c:pt idx="0"><c:v>0</c:v></c:pt></c:numCache></c:numRef></c:val></c:ser>
</c:barChart></c:plotArea></c:chart></c:chartSpace>
XML;

        $updater = new ManagementReportChartUpdater;
        $updated = $updater->apply($xml, [
            'numerators' => [100, 200],
            'denominators' => [300, 400],
            'percentages' => [75, 80],
            'meta' => 90,
        ]);

        $this->assertStringContainsString('<c:v>100</c:v>', $updated);
        $this->assertStringContainsString('<c:v>300</c:v>', $updated);
        $this->assertStringContainsString('<c:v>0.75</c:v>', $updated);
        $this->assertStringContainsString('<c:v>0.9</c:v>', $updated);
    }
}
