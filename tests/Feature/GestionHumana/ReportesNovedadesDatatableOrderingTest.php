<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Models\ReportesNovedadesPermiso;
use App\Models\ReportesNovedadesRetiro;
use App\Models\ReportesNovedadesVacacion;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * DataTables server-side: el endpoint debe respetar order[0][column]/order[0][dir]
 * en lugar de devolver siempre el orden fijo por fecha.
 */
class ReportesNovedadesDatatableOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_vacaciones_datatable_orders_by_requested_column(): void
    {
        $user = $this->sheetEditor('vacaciones');

        ReportesNovedadesVacacion::query()->create($this->vacacionRow('ALFA UNO', '2026-09-01'));
        ReportesNovedadesVacacion::query()->create($this->vacacionRow('ZETA DOS', '2026-09-10'));

        $asc = $this->orderRequest($user, 'vacaciones', 1, 'asc');
        $this->assertSame(2, $asc->json('recordsFiltered'));
        $this->assertStringContainsString('ALFA UNO', (string) $asc->json('data.0.1'));

        $desc = $this->orderRequest($user, 'vacaciones', 1, 'desc');
        $this->assertStringContainsString('ZETA DOS', (string) $desc->json('data.0.1'));

        // Sin order explicito conserva el default: inicio mas reciente primero.
        $default = $this->actingAs($user)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable', $this->periodParams()))
            ->assertOk();
        $this->assertStringContainsString('ZETA DOS', (string) $default->json('data.0.1'));
    }

    public function test_permisos_datatable_orders_by_requested_column(): void
    {
        $user = $this->sheetEditor('permisos');

        ReportesNovedadesPermiso::query()->create($this->permisoRow('ALFA UNO', '2026-09-01'));
        ReportesNovedadesPermiso::query()->create($this->permisoRow('ZETA DOS', '2026-09-10'));

        $asc = $this->orderRequest($user, 'permisos', 1, 'asc');
        $this->assertStringContainsString('ALFA UNO', (string) $asc->json('data.0.1'));

        $desc = $this->orderRequest($user, 'permisos', 1, 'desc');
        $this->assertStringContainsString('ZETA DOS', (string) $desc->json('data.0.1'));
    }

    public function test_incapacidades_datatable_orders_by_requested_column(): void
    {
        $user = $this->sheetEditor('incapacidades');

        ReportesNovedadesIncapacidad::query()->create($this->incapacidadRow('ALFA UNO', '2026-09-01'));
        ReportesNovedadesIncapacidad::query()->create($this->incapacidadRow('ZETA DOS', '2026-09-10'));

        $asc = $this->orderRequest($user, 'incapacidades', 1, 'asc');
        $this->assertStringContainsString('ALFA UNO', (string) $asc->json('data.0.1'));

        $desc = $this->orderRequest($user, 'incapacidades', 1, 'desc');
        $this->assertStringContainsString('ZETA DOS', (string) $desc->json('data.0.1'));

        // "Dias entrega" es columna calculada: no debe romper, cae al orden por defecto.
        $computed = $this->orderRequest($user, 'incapacidades', 9, 'asc');
        $this->assertSame(2, $computed->json('recordsFiltered'));
        $this->assertStringContainsString('ZETA DOS', (string) $computed->json('data.0.1'));
    }

    public function test_retiros_datatable_orders_by_requested_column(): void
    {
        $user = $this->sheetEditor('retiros');

        ReportesNovedadesRetiro::query()->create($this->retiroRow('ALFA UNO', '2026-09-01'));
        ReportesNovedadesRetiro::query()->create($this->retiroRow('ZETA DOS', '2026-09-10'));

        $asc = $this->orderRequest($user, 'retiros', 1, 'asc');
        $this->assertStringContainsString('ALFA UNO', (string) $asc->json('data.0.1'));

        $desc = $this->orderRequest($user, 'retiros', 1, 'desc');
        $this->assertStringContainsString('ZETA DOS', (string) $desc->json('data.0.1'));
    }

    private function sheetEditor(string $sheet): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo("reportes_novedades.{$sheet}.edit");

        return $user;
    }

    private function orderRequest(User $user, string $sheet, int $column, string $direction): TestResponse
    {
        return $this->actingAs($user)
            ->getJson(route("gestion-humana.reportes-novedades.{$sheet}.datatable", array_merge(
                $this->periodParams(),
                ['order' => [['column' => $column, 'dir' => $direction]]],
            )))
            ->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function periodParams(): array
    {
        return [
            'fecha_desde' => '2020-01-01',
            'fecha_hasta' => '2030-12-31',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vacacionRow(string $name, string $fechaInicio): array
    {
        return [
            'document_number' => (string) random_int(1000000000, 1999999999),
            'employee_name' => $name,
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => $fechaInicio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permisoRow(string $name, string $fechaInicio): array
    {
        return [
            'document_number' => (string) random_int(2000000000, 2999999999),
            'employee_name' => $name,
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaInicio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incapacidadRow(string $name, string $fechaInicio): array
    {
        return [
            'document_number' => (string) random_int(3000000000, 3999999999),
            'employee_name' => $name,
            'tipo_incapacidad' => 'EG',
            'dias' => 4,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaInicio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function retiroRow(string $name, string $fechaRetiro): array
    {
        return [
            'document_number' => (string) random_int(4000000000, 4999999999),
            'employee_name' => $name,
            'novedad' => 'RETIRO',
            'fecha_retiro' => $fechaRetiro,
            'motivo_retiro' => 'RENUNCIA',
        ];
    }
}
