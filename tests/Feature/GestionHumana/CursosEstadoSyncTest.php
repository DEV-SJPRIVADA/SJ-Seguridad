<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeCurso;
use App\Services\GestionHumana\EmployeeCursoEstadoSyncService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CursosEstadoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_resolve_estado_from_vigencia(): void
    {
        $sync = app(EmployeeCursoEstadoSyncService::class);

        $vigente = EmployeeCurso::factory()->make(['fecha_expedicion' => '2025-10-15']);
        $actualizar = EmployeeCurso::factory()->make(['fecha_expedicion' => '2025-10-14']);
        $vencido = EmployeeCurso::factory()->make(['fecha_expedicion' => '2025-09-15']);

        $this->assertSame(EmployeeCurso::ESTADO_ACTUALIZADO, $sync->resolveEstadoFromVigencia($vigente));
        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $sync->resolveEstadoFromVigencia($actualizar));
        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $sync->resolveEstadoFromVigencia($vencido));
    }

    public function test_sync_preserves_solicitado_unless_backfill_force_vigente(): void
    {
        $sync = app(EmployeeCursoEstadoSyncService::class);

        $solicitadoActualizar = EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2025-10-14',
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
        ]);
        $actualizadoVencido = EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2025-09-15',
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
        ]);
        $solicitadoVigente = EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2025-10-15',
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
        ]);
        $nullVigente = EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2025-10-15',
            'estado' => null,
        ]);

        $result = $sync->syncAll(backfill: false);
        $this->assertSame(4, $result['scanned']);
        $this->assertGreaterThanOrEqual(2, $result['updated']);

        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $solicitadoActualizar->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $actualizadoVencido->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $solicitadoVigente->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_ACTUALIZADO, $nullVigente->fresh()->estado);

        $sync->syncAll(backfill: true);
        $this->assertSame(EmployeeCurso::ESTADO_ACTUALIZADO, $solicitadoVigente->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $solicitadoActualizar->fresh()->estado);
    }

    public function test_artisan_command_syncs_estados(): void
    {
        EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2025-09-15',
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
        ]);

        $this->artisan('cursos:sync-estados')
            ->assertSuccessful();

        $this->assertSame(
            EmployeeCurso::ESTADO_PENDIENTE,
            EmployeeCurso::query()->value('estado'),
        );
    }
}
