<?php

namespace Tests\Unit;

use App\Services\Indicadores\ManagementReport\ManagementReportChartSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManagementReportChartSanitizerTest extends TestCase
{
    #[Test]
    public function it_removes_formula_refs_and_external_formulas(): void
    {
        $xml = <<<'XML'
<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:ns4="http://schemas.microsoft.com/office/drawing/2012/chart">
<c:chart><c:plotArea><c:barChart><c:ser><c:cat><c:strRef><c:f>Sheet1!$A$1</c:f><c:strCache/></c:strRef></c:cat>
<c:val><c:numRef><c:extLst><c:ext uri="{02D57815-91ED-43cb-92C2-25804820EDAC}"><ns4:formulaRef><ns4:sqref>Sheet1!$B$1</ns4:sqref></ns4:formulaRef></c:ext></c:extLst><c:numCache/></c:numRef></c:val></c:ser></c:barChart></c:plotArea></c:chart>
<c:externalData r:id="rId99"/></c:chartSpace>
XML;

        $sanitized = (new ManagementReportChartSanitizer)->sanitize($xml);

        $this->assertStringNotContainsString('formulaRef', $sanitized);
        $this->assertStringNotContainsString('<c:f>', $sanitized);
        $this->assertStringNotContainsString('externalData', $sanitized);
    }

    #[Test]
    public function it_removes_empty_extension_lists_left_by_filtered_series_cleanup(): void
    {
        $xml = <<<'XML'
<c:bar3DChart><c:axId val="1658577039"/><c:axId val="1658580399"/><c:axId val="0"/><c:extLst></c:extLst></c:bar3DChart>
XML;

        $sanitized = (new ManagementReportChartSanitizer)->sanitize($xml);

        $this->assertStringNotContainsString('extLst', $sanitized);
        $this->assertStringNotContainsString('axId val="0"', $sanitized);
    }
}
