<?php

namespace Tests\Unit;

use App\Services\Indicadores\ManagementReport\ManagementReportPptxTextFormatter;
use PHPUnit\Framework\TestCase;

class ManagementReportPptxTextFormatterTest extends TestCase
{
    public function test_escape_xml_special_characters(): void
    {
        $formatter = new ManagementReportPptxTextFormatter;

        $this->assertSame('A &amp; B &lt; 50%', $formatter->escape('A & B < 50%'));
    }
}
