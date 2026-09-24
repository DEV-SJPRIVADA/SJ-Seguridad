<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Services\GestionHumana\AcreditacionEstadoCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionEstadoCalculatorTest extends TestCase
{
    private AcreditacionEstadoCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AcreditacionEstadoCalculator;
    }

    public function test_fecha_solicitud_wins_even_when_vigencia_vencida(): void
    {
        $today = Carbon::parse('2026-09-23');

        $estado = $this->calculator->calculate(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-01-01'),
            $today,
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $estado);
    }

    public function test_solo_fecha_solicitud_es_en_proceso(): void
    {
        $estado = $this->calculator->calculate(
            Carbon::parse('2026-09-01'),
            null,
            Carbon::parse('2026-09-23'),
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $estado);
    }

    public function test_vigencia_vencida_es_desacreditado(): void
    {
        $estado = $this->calculator->calculate(
            null,
            Carbon::parse('2026-09-23'),
            Carbon::parse('2026-09-23'),
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_DESACREDITADO, $estado);
    }

    public function test_vigencia_dentro_de_21_dias_es_por_vencer(): void
    {
        $today = Carbon::parse('2026-09-23');

        $estado = $this->calculator->calculate(
            null,
            $today->copy()->addDays(21),
            $today,
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_POR_VENCER, $estado);
    }

    public function test_vigencia_mas_alla_de_21_dias_es_acreditado(): void
    {
        $today = Carbon::parse('2026-09-23');

        $estado = $this->calculator->calculate(
            null,
            $today->copy()->addDays(22),
            $today,
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_ACREDITADO, $estado);
    }

    public function test_sin_vigencia_ni_solicitud_es_en_proceso(): void
    {
        $estado = $this->calculator->calculate(
            null,
            null,
            Carbon::parse('2026-09-23'),
        );

        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $estado);
    }
}
