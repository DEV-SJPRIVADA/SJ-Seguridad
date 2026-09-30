<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\ReportesNovedadesPermiso;
use App\Models\ReportesNovedadesVacacion;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReportesNovedadesVacacionesPermisosTest extends TestCase
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

    public function test_edit_implies_view_for_vacaciones_datatable(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable'))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_review_can_view_and_export_but_cannot_store_vacaciones(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload())
            ->assertForbidden();
    }

    public function test_vacaciones_crud_store_update_destroy(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '1001001001',
                'employee_name' => 'Ana Vacaciones',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));

        $row = ReportesNovedadesVacacion::query()->where('document_number', '1001001001')->first();
        $this->assertNotNull($row);
        $this->assertSame('VACACIONES DISF', $row->novedad);
        $this->assertSame('2026-09-01', optional($row->fecha_inicio)?->format('Y-m-d'));
        $this->assertNull($row->observacion_nomina);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.update', $row), $this->vacacionPayload([
                'document_number' => '1001001001',
                'employee_name' => 'Ana Vacaciones Editada',
                'novedad' => 'VACACIONES COMP',
                'dias_novedad' => 10,
            ]))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Ana Vacaciones Editada', $row->employee_name);
        $this->assertSame('VACACIONES COMP', $row->novedad);
        $this->assertSame(10, $row->dias_novedad);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.vacaciones.destroy', $row))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));

        $this->assertDatabaseMissing('reportes_novedades_vacaciones', ['id' => $row->id]);
    }

    public function test_gh_update_does_not_persist_observacion_nomina(): void
    {
        $editor = $this->editorUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'observacion_nomina' => 'OK previo',
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.update', $row), array_merge(
                $this->vacacionPayload(['employee_name' => 'Sin tocar nomina']),
                ['observacion_nomina' => 'Intento GH']
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Sin tocar nomina', $row->employee_name);
        $this->assertSame('OK previo', $row->observacion_nomina);
    }

    public function test_review_persists_only_observacion_nomina_and_ignores_gh_fields(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'employee_name' => 'Original GH',
            'observacion_nomina' => null,
        ]));

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.review', $row), [
                'observacion_nomina' => 'OK Nomina',
                'employee_name' => 'Hackeado',
                'novedad' => 'VACACIONES COMP',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('OK Nomina', $row->observacion_nomina);
        $this->assertSame('Original GH', $row->employee_name);
        $this->assertSame('VACACIONES DISF', $row->novedad);
    }

    public function test_review_cannot_destroy_vacaciones(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes());

        $this->actingAs($reviewer)
            ->delete(route('gestion-humana.reportes-novedades.vacaciones.destroy', $row))
            ->assertForbidden();

        $this->assertDatabaseHas('reportes_novedades_vacaciones', ['id' => $row->id]);
    }

    public function test_invalid_vacaciones_novedad_returns_validation_error(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.vacaciones'))
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'novedad' => 'NOVEDAD INVALIDA',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertSessionHasErrors('novedad');

        $this->assertSame(0, ReportesNovedadesVacacion::query()->count());
    }

    public function test_vacaciones_export_ok_with_view_and_review_forbidden_without(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo('reportes_novedades.vacaciones.view');

        $reviewer = $this->reviewerUser('vacaciones');
        $stranger = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertForbidden();
    }

    public function test_vacaciones_historial_returns_events_after_create(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '2002002002',
            ]))
            ->assertRedirect();

        $row = ReportesNovedadesVacacion::query()->where('document_number', '2002002002')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('module', 'reportes_novedades')
                ->where('event_type', 'vacaciones_novedad')
                ->where('action', 'create')
                ->exists()
        );

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.historial', ['id' => $row->id]))
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['action' => 'create']);
    }

    public function test_permisos_smoke_crud_review_export_and_invalid_catalog(): void
    {
        $editor = $this->editorUser('permisos');
        $reviewer = $this->reviewerUser('permisos');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload([
                'document_number' => '3003003003',
                'employee_name' => 'Pedro Permiso',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.permisos'));

        $row = ReportesNovedadesPermiso::query()->where('document_number', '3003003003')->firstOrFail();
        $this->assertFalse((bool) $row->marca_gh);

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.permisos'))
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload([
                'novedad' => 'INVALIDO',
            ]))
            ->assertSessionHasErrors('novedad');

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.permisos.review', $row), [
                'observacion_nomina' => 'Revisado',
                'employee_name' => 'No debe cambiar',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Revisado', $row->observacion_nomina);
        $this->assertSame('Pedro Permiso', $row->employee_name);

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.permisos.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload())
            ->assertForbidden();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.permisos.update', $row), array_merge(
                $this->permisoPayload([
                    'document_number' => '3003003003',
                    'employee_name' => 'Pedro Actualizado',
                    'marca_gh' => '1',
                ]),
                ['observacion_nomina' => 'Ignorar']
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Pedro Actualizado', $row->employee_name);
        $this->assertTrue((bool) $row->marca_gh);
        $this->assertSame('Revisado', $row->observacion_nomina);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.permisos.destroy', $row))
            ->assertRedirect();

        $this->assertDatabaseMissing('reportes_novedades_permisos', ['id' => $row->id]);
    }

    private function editorUser(string $sheet): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo("reportes_novedades.{$sheet}.edit");

        return $user;
    }

    private function reviewerUser(string $sheet): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo("reportes_novedades.{$sheet}.review");

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vacacionPayload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '1010101010',
            'employee_name' => 'Empleado Vacacion',
            'cargo' => 'Guardia',
            'destino' => 'Cliente A',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-09-01',
            'observaciones' => 'Obs GH',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vacacionAttributes(array $overrides = []): array
    {
        return $this->vacacionPayload($overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function permisoPayload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '4040404040',
            'employee_name' => 'Empleado Permiso',
            'tipo' => 'Operativo',
            'cargo' => 'Supervisor',
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-12',
            'marca_gh' => '0',
        ], $overrides);
    }
}
