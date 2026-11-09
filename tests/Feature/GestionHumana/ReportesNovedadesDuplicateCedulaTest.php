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
use Tests\TestCase;

class ReportesNovedadesDuplicateCedulaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'reportes_novedades.vacaciones.edit',
            'reportes_novedades.incapacidades.edit',
            'reportes_novedades.retiros.edit',
            'reportes_novedades.permisos.edit',
        ]);

        return $user;
    }

    public function test_vacaciones_datatable_marks_duplicate_cedula_in_red(): void
    {
        $user = $this->editorUser();

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 1',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-14',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesVacacion::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 2',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-09-11',
            'fecha_fin' => '2026-09-15',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesVacacion::query()->create([
            'document_number' => '2222222222',
            'employee_name' => 'UNICO',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-09-12',
            'fecha_fin' => '2026-09-16',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable', [
                'fecha_desde' => '2026-09-01',
                'fecha_hasta' => '2026-09-30',
            ]))
            ->assertOk();

        $cells = collect($response->json('data'))->map(fn (array $row) => $row[0])->values()->all();
        $joined = implode(' ', $cells);

        $this->assertSame(2, substr_count($joined, 'rn-cedula-duplicada'));
        $this->assertStringNotContainsString('rn-cedula-duplicada', (string) collect($response->json('data'))
            ->first(fn (array $row) => str_contains((string) $row[0], '2222222222'))[0]);
    }

    public function test_permisos_datatable_marks_duplicate_cedula_in_red(): void
    {
        $user = $this->editorUser();

        ReportesNovedadesPermiso::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 1',
            'tipo' => 'Operativo',
            'cargo' => 'Supervisor',
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-12',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesPermiso::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 2',
            'tipo' => 'Operativo',
            'cargo' => 'Supervisor',
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-09-11',
            'fecha_fin' => '2026-09-13',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesPermiso::query()->create([
            'document_number' => '2222222222',
            'employee_name' => 'UNICO',
            'tipo' => 'Operativo',
            'cargo' => 'Supervisor',
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-09-12',
            'fecha_fin' => '2026-09-14',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('gestion-humana.reportes-novedades.permisos.datatable', [
                'fecha_desde' => '2026-09-01',
                'fecha_hasta' => '2026-09-30',
            ]))
            ->assertOk();

        $joined = implode(' ', collect($response->json('data'))->map(fn (array $row) => $row[0])->values()->all());
        $this->assertSame(2, substr_count($joined, 'rn-cedula-duplicada'));
    }

    public function test_incapacidades_datatable_marks_duplicate_cedula_in_red(): void
    {
        $user = $this->editorUser();

        foreach ([10, 11] as $day) {
            ReportesNovedadesIncapacidad::query()->create([
                'document_number' => '1111111111',
                'employee_name' => 'DUP '.$day,
                'cargo' => 'Guardia',
                'destino' => 'Cliente',
                'tipo_incapacidad' => 'EG',
                'dias' => 3,
                'fecha_inicio' => '2026-09-'.$day,
                'fecha_fin' => '2026-09-'.($day + 2),
                'fecha_recepcion' => '2026-09-'.$day,
                'extemporanea' => false,
                'observaciones' => 'Obs',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        ReportesNovedadesIncapacidad::query()->create([
            'document_number' => '2222222222',
            'employee_name' => 'UNICO',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'tipo_incapacidad' => 'EG',
            'dias' => 3,
            'fecha_inicio' => '2026-09-12',
            'fecha_fin' => '2026-09-14',
            'fecha_recepcion' => '2026-09-12',
            'extemporanea' => false,
            'observaciones' => 'Obs',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.datatable', [
                'fecha_desde' => '2026-09-01',
                'fecha_hasta' => '2026-09-30',
            ]))
            ->assertOk();

        $joined = implode(' ', collect($response->json('data'))->map(fn (array $row) => $row[0])->values()->all());
        $this->assertSame(2, substr_count($joined, 'rn-cedula-duplicada'));
    }

    public function test_retiros_datatable_marks_duplicate_cedula_in_red(): void
    {
        $user = $this->editorUser();

        ReportesNovedadesRetiro::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 1',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'RETIRO',
            'fecha_retiro' => '2026-09-10',
            'motivo_retiro' => 'RENUNCIA',
            'observaciones' => 'Obs',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesRetiro::query()->create([
            'document_number' => '1111111111',
            'employee_name' => 'DUP 2',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'RETIRO',
            'fecha_retiro' => '2026-09-11',
            'motivo_retiro' => 'RENUNCIA',
            'observaciones' => 'Obs',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ReportesNovedadesRetiro::query()->create([
            'document_number' => '2222222222',
            'employee_name' => 'UNICO',
            'cargo' => 'Guardia',
            'destino' => 'Cliente',
            'novedad' => 'RETIRO',
            'fecha_retiro' => '2026-09-12',
            'motivo_retiro' => 'RENUNCIA',
            'observaciones' => 'Obs',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('gestion-humana.reportes-novedades.retiros.datatable', [
                'fecha_desde' => '2026-09-01',
                'fecha_hasta' => '2026-09-30',
            ]))
            ->assertOk();

        $joined = implode(' ', collect($response->json('data'))->map(fn (array $row) => $row[0])->values()->all());
        $this->assertSame(2, substr_count($joined, 'rn-cedula-duplicada'));
    }
}
