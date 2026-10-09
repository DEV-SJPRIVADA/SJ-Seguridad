<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Services\GestionHumana\MtSt04EstadoCalculator;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MtSt04EstadoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sync_all_recalculates_estados_with_live_cargo(): void
    {
        $this->createFicha('1002003001', 'Ana', 'SUPERVISOR');
        $this->createFicha('1002003002', 'Luis', 'GUARDA');

        // Vencimiento 1 en ventana VENCERA (hoy + 10 días) pero estado desactualizado.
        $vencera = MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'fecha_examen_1' => '2025-10-20', // +364 ≈ 2026-10-19 → VENCERA
            'fecha_vencimiento_1' => '2026-10-19',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'fecha_examen_2' => '2025-10-20',
            'fecha_vencimiento_2' => '2026-10-19',
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
        ]);

        // Cargo GUARDA → ESTADO2 debe pasar a NO APLICA.
        $noAplica = MtSt04Registro::factory()->create([
            'document_number' => '1002003002',
            'fecha_examen_1' => '2024-01-01',
            'fecha_vencimiento_1' => '2024-12-30',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'fecha_examen_2' => '2025-01-01',
            'fecha_vencimiento_2' => '2025-12-31',
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
        ]);

        $result = app(MtSt04EstadoCalculator::class)->syncAll();

        $this->assertSame(2, $result['scanned']);
        $this->assertGreaterThanOrEqual(2, $result['updated']);

        $this->assertSame(MtSt04Registro::ESTADO_VENCERA, $vencera->fresh()->estado_1);
        $this->assertSame(MtSt04Registro::ESTADO_VENCERA, $vencera->fresh()->estado_2);
        $this->assertSame(MtSt04Registro::ESTADO_VENCIDO, $noAplica->fresh()->estado_1);
        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $noAplica->fresh()->estado_2);
    }

    public function test_artisan_command_dry_run_does_not_persist(): void
    {
        $this->createFicha('1002003001', 'Ana', 'SUPERVISOR');

        $row = MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'fecha_examen_1' => '2024-01-01',
            'fecha_vencimiento_1' => '2024-12-30',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'fecha_examen_2' => null,
            'fecha_vencimiento_2' => null,
            'estado_2' => null,
        ]);

        $this->artisan('mt_st_04:sync-estados', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry-run');

        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $row->fresh()->estado_1);
    }

    public function test_artisan_command_updates_estados_and_audits(): void
    {
        $this->createFicha('1002003001', 'Ana', 'OPERADOR');

        $row = MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'fecha_examen_1' => '2024-01-01',
            'fecha_vencimiento_1' => '2024-12-30',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'fecha_examen_2' => '2025-01-01',
            'fecha_vencimiento_2' => '2025-12-31',
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
        ]);

        $this->artisan('mt_st_04:sync-estados')
            ->assertSuccessful()
            ->expectsOutputToContain('actualizados');

        $fresh = $row->fresh();
        $this->assertSame(MtSt04Registro::ESTADO_VENCIDO, $fresh->estado_1);
        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $fresh->estado_2);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'mt_st_04',
            'action' => 'sync_estados',
        ]);
    }

    public function test_artisan_command_respects_date_option(): void
    {
        $this->createFicha('1002003001', 'Ana', 'SUPERVISOR');

        // Con fecha de referencia lejana, vencimiento 2026-10-19 ya VENCIDO.
        $row = MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'fecha_examen_1' => '2025-10-20',
            'fecha_vencimiento_1' => '2026-10-19',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'fecha_examen_2' => null,
            'fecha_vencimiento_2' => null,
            'estado_2' => null,
        ]);

        $this->artisan('mt_st_04:sync-estados', ['--date' => '2026-11-01'])
            ->assertSuccessful();

        $this->assertSame(MtSt04Registro::ESTADO_VENCIDO, $row->fresh()->estado_1);
    }

    public function test_schedule_registers_mt_st_04_sync_at_0625_bogota(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $match = $events->first(function ($event): bool {
            $command = (string) ($event->command ?? '');

            return str_contains($command, 'mt_st_04:sync-estados');
        });

        $this->assertNotNull($match, 'Schedule debe incluir mt_st_04:sync-estados');
        $this->assertSame('25 6 * * *', $match->expression);
        $this->assertSame('America/Bogota', (string) $match->timezone);
    }

    private function createFicha(
        string $documentNumber,
        string $fullName,
        string $positionName = 'SUPERVISOR',
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'employment_status' => $employmentStatus,
        ]);
    }
}
