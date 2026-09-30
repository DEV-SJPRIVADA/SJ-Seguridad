<?php

namespace Tests\Unit\Support;

use App\Support\ReportesNovedadesUi;
use PHPUnit\Framework\TestCase;

class ReportesNovedadesUiTest extends TestCase
{
    public function test_empty_observacion_renders_placeholder(): void
    {
        $this->assertSame('—', ReportesNovedadesUi::observacionNominaCell(null));
        $this->assertSame('—', ReportesNovedadesUi::observacionNominaCell('   '));
    }

    public function test_filled_observacion_renders_success_pill(): void
    {
        $html = ReportesNovedadesUi::observacionNominaCell('OK Nomina');

        $this->assertStringContainsString('status-pill--success', $html);
        $this->assertStringContainsString('OK Nomina', $html);
    }
}
