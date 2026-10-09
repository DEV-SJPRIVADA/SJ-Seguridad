<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MtSt04MatrizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_viewer_can_see_matriz_and_datatable(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');
        MtSt04Registro::factory()->create(['document_number' => '1002003001']);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz'))
            ->assertOk()
            ->assertSee('Matriz', false);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_datatable_default_solo_activos_ficha(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createFicha('1002003002', 'Luis Retiro', 'SUPERVISOR', EmployeeFichaProfile::STATUS_DESVINCULADO);
        MtSt04Registro::factory()->create(['document_number' => '1002003001']);
        MtSt04Registro::factory()->create(['document_number' => '1002003002']);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'ficha_estado' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'ficha_estado' => 'todos',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);
    }

    public function test_viewer_forbidden_on_store_lookup_and_destroy(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa');
        $registro = MtSt04Registro::factory()->create(['document_number' => '1002003001']);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'arma' => 'SI',
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.lookup', ['cedula' => '1002003001']))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('gestion-humana.mt-st-04.matriz.destroy', $registro))
            ->assertForbidden();
    }

    public function test_editor_can_crud_and_lookup(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO, [
            'work_city_name' => 'Bogotá',
            'cost_center_name' => 'CC-01',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay());

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.mt-st-04.matriz.lookup', ['cedula' => '1002003001']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('full_name', 'Ana Activa')
            ->assertJsonPath('cargo', 'SUPERVISOR')
            ->assertJsonPath('ciudad', 'Bogotá')
            ->assertJsonPath('puesto', 'CC-01');

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'arma' => 'SI',
                'fecha_examen_1' => '2026-01-01',
                'apto' => 'SI',
                'observaciones_1' => 'OK',
                'fecha_examen_2' => '2026-02-01',
                'observaciones_2' => 'Vial',
            ])
            ->assertRedirect(route('gestion-humana.mt-st-04.matriz', ['ficha_estado' => 'activo']));

        $registro = MtSt04Registro::query()->where('document_number', '1002003001')->first();
        $this->assertNotNull($registro);
        $this->assertSame('2026-12-31', $registro->fecha_vencimiento_1?->toDateString());
        $this->assertSame('2027-01-31', $registro->fecha_vencimiento_2?->toDateString());
        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $registro->estado_1);
        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $registro->estado_2);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.mt-st-04.matriz.update', $registro), [
                'document_number' => '1002003001',
                'arma' => 'NO',
                'fecha_examen_1' => '2026-01-01',
                'apto' => 'NO',
                'observaciones_1' => 'Actualizado',
                'fecha_examen_2' => '2026-02-01',
            ])
            ->assertRedirect();

        $registro->refresh();
        $this->assertSame('NO', $registro->arma);
        $this->assertSame('Actualizado', $registro->observaciones_1);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.mt-st-04.matriz.destroy', $registro))
            ->assertRedirect(route('gestion-humana.mt-st-04.matriz'));

        $this->assertDatabaseMissing('mt_st_04_registros', ['id' => $registro->id]);

        Carbon::setTestNow();
    }

    public function test_datatable_filters_by_ciudad(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Cali', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO, [
            'residence_city_name' => 'CALI',
        ]);
        $this->createFicha('1002003002', 'Luis Bogota', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO, [
            'residence_city_name' => 'BOGOTA',
        ]);
        MtSt04Registro::factory()->create(['document_number' => '1002003001']);
        MtSt04Registro::factory()->create(['document_number' => '1002003002']);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'ciudad' => 'CALI',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('Ana Cali', false)
            ->assertDontSee('Luis Bogota', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz', ['ciudad' => 'CALI']))
            ->assertOk()
            ->assertSee('id="filter_ciudad"', false);
    }

    public function test_datatable_and_lookup_use_residence_city_when_work_city_empty(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO, [
            'work_city_name' => null,
            'residence_city_name' => 'Cali',
            'cost_center_name' => 'CC-01',
        ]);
        MtSt04Registro::factory()->create(['document_number' => '1002003001']);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.mt-st-04.matriz.lookup', ['cedula' => '1002003001']))
            ->assertOk()
            ->assertJsonPath('ciudad', 'Cali');

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'ficha_estado' => 'activo',
            ]))
            ->assertOk()
            ->assertSee('Cali', false);
    }

    public function test_store_allows_orphan_cedula_and_datatable_marks_sin_ficha(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.mt-st-04.matriz'))
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '9999999999',
                'arma' => 'SI',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mt_st_04_registros', ['document_number' => '9999999999']);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.mt-st-04.matriz.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'ficha_estado' => 'activo',
            ]))
            ->assertOk()
            ->assertSee('Sin Ficha', false)
            ->assertSee('9999999999', false);
    }

    public function test_store_rejects_duplicate_document_number(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa');
        MtSt04Registro::factory()->create(['document_number' => '1002003001']);

        $this->actingAs($editor)
            ->from(route('gestion-humana.mt-st-04.matriz'))
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'arma' => 'SI',
            ])
            ->assertSessionHasErrors('document_number');

        $this->assertDatabaseCount('mt_st_04_registros', 1);
    }

    public function test_store_estado2_no_aplica_para_guarda(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Guarda Uno', 'GUARDA');

        Carbon::setTestNow(Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay());

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'fecha_examen_1' => '2026-01-01',
                'fecha_examen_2' => '2026-01-01',
            ])
            ->assertRedirect();

        $registro = MtSt04Registro::query()->where('document_number', '1002003001')->first();
        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $registro?->estado_2);
        $this->assertNotSame(MtSt04Registro::ESTADO_NO_APLICA, $registro?->estado_1);

        Carbon::setTestNow();
    }

    public function test_store_estado_vencera_ventana_30_dias(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');

        // hoy = 2026-10-09 → vencimiento = examen + 364; para VENCERA en borde día 30:
        // vencimiento = hoy + 30 = 2026-11-08 → examen = 2026-11-08 - 364 = 2025-11-09
        Carbon::setTestNow(Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay());

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'fecha_examen_1' => '2025-11-09',
            ])
            ->assertRedirect();

        $registro = MtSt04Registro::query()->where('document_number', '1002003001')->first();
        $this->assertSame('2026-11-08', $registro?->fecha_vencimiento_1?->toDateString());
        $this->assertSame(MtSt04Registro::ESTADO_VENCERA, $registro?->estado_1);

        Carbon::setTestNow();
    }

    public function test_edit_without_view_spatie_can_mutate(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.edit',
        ]);
        $this->createFicha('1002003001', 'Ana Activa');

        $this->actingAs($user)
            ->post(route('gestion-humana.mt-st-04.matriz.store'), [
                'document_number' => '1002003001',
                'arma' => 'SI',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mt_st_04_registros', ['document_number' => '1002003001']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createFicha(
        string $documentNumber,
        string $fullName,
        string $positionName = 'SUPERVISOR',
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
        array $extra = [],
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'employment_status' => $employmentStatus,
        ], $extra));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
            'mt_st_04.edit',
        ]);

        return $user;
    }
}
