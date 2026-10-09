<?php

namespace Tests\Feature\GestionHumana;

use App\Models\MtSt04Registro;
use App\Services\GestionHumana\MtSt04EstadoCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MtSt04EstadoCalculatorTest extends TestCase
{
    private MtSt04EstadoCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new MtSt04EstadoCalculator;
    }

    public function test_vencimiento_es_examen_mas_364(): void
    {
        $examen = Carbon::parse('2025-01-01');
        $venc = $this->calculator->calculateVencimiento($examen);

        $this->assertSame('2025-12-31', $venc?->toDateString());
    }

    public function test_sin_fecha_examen_vencimiento_null(): void
    {
        $this->assertNull($this->calculator->calculateVencimiento(null));
        $this->assertNull($this->calculator->calculateEstadoVigencia(null));
    }

    public function test_estado_vencido_antes_de_hoy(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstadoVigencia(
            $hoy->copy()->subDay(),
            $hoy,
        );

        $this->assertSame(MtSt04Registro::ESTADO_VENCIDO, $estado);
    }

    public function test_estado_vencera_en_dia_de_vencimiento(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstadoVigencia($hoy, $hoy);

        $this->assertSame(MtSt04Registro::ESTADO_VENCERA, $estado);
    }

    public function test_estado_vencera_borde_dia_30_inclusive(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstadoVigencia(
            $hoy->copy()->addDays(30),
            $hoy,
        );

        $this->assertSame(MtSt04Registro::ESTADO_VENCERA, $estado);
    }

    public function test_estado_vigente_dia_31(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstadoVigencia(
            $hoy->copy()->addDays(31),
            $hoy,
        );

        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $estado);
    }

    public function test_estado2_no_aplica_guarda_exacto(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstado2(
            '  guarda  ',
            $hoy->copy()->addYear(),
            $hoy,
        );

        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $estado);
    }

    public function test_estado2_no_aplica_operador_exacto(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstado2('OPERADOR', null, $hoy);

        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $estado);
    }

    public function test_estado2_cargo_compuesto_no_es_no_aplica(): void
    {
        $hoy = Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay();

        $estado = $this->calculator->calculateEstado2(
            'GUARDA SJ',
            $hoy->copy()->addDays(60),
            $hoy,
        );

        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $estado);
    }
}
