<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Services\GestionHumana\AcreditacionEstadoCalculator;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionesEstadoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-23', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sync_all_recalculates_por_vencer_and_desacreditado(): void
    {
        $porVencer = AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2026-10-01', // hoy + 8 días → POR_VENCER
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $desacreditado = AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2026-09-20', // ≤ hoy → DESACREDITADO
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $sigueAcreditado = AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2027-01-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $enProceso = AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => '2026-09-01',
            'vigencia_acr' => '2026-09-20',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $result = app(AcreditacionEstadoCalculator::class)->syncAll();

        $this->assertSame(4, $result['scanned']);
        $this->assertGreaterThanOrEqual(3, $result['updated']);

        $this->assertSame(AcreditacionAcreditado::ESTADO_POR_VENCER, $porVencer->fresh()->estado);
        $this->assertSame(AcreditacionAcreditado::ESTADO_DESACREDITADO, $desacreditado->fresh()->estado);
        $this->assertSame(AcreditacionAcreditado::ESTADO_ACREDITADO, $sigueAcreditado->fresh()->estado);
        $this->assertSame(AcreditacionAcreditado::ESTADO_EN_PROCESO, $enProceso->fresh()->estado);
    }

    public function test_artisan_command_syncs_estados_with_calendar(): void
    {
        AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2026-09-20',
            'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
        ]);

        AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2026-10-05',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $this->artisan('acreditaciones:sync-estados')
            ->assertSuccessful();

        $estados = AcreditacionAcreditado::query()->orderBy('id')->pluck('estado')->all();

        $this->assertSame(AcreditacionAcreditado::ESTADO_DESACREDITADO, $estados[0]);
        $this->assertSame(AcreditacionAcreditado::ESTADO_POR_VENCER, $estados[1]);
    }

    public function test_artisan_command_respects_date_option(): void
    {
        $row = AcreditacionAcreditado::factory()->create([
            'fecha_solicitud' => null,
            'vigencia_acr' => '2026-10-01',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        // Con fecha de referencia lejana, vigencia ya vencida → DESACREDITADO
        $this->artisan('acreditaciones:sync-estados', ['--date' => '2026-10-15'])
            ->assertSuccessful();

        $this->assertSame(
            AcreditacionAcreditado::ESTADO_DESACREDITADO,
            $row->fresh()->estado,
        );
    }
}
