<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MtSt04ValidacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_validaciones_tab_requires_view(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.validaciones'))
            ->assertForbidden();
    }

    public function test_validaciones_lists_active_requiring_not_in_matriz(): void
    {
        $viewer = $this->userWithMtSt04(['mt_st_04.view', 'view.board.gestion_humana.mt_st_04']);

        $pending = EmployeeFichaProfile::query()->create([
            'document_number' => '9001001',
            'full_name' => 'PENDIENTE VALIDACION',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
            'position_name' => 'ESCOLTA',
        ]);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9001002',
            'full_name' => 'YA EN MATRIZ',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
        ]);
        MtSt04Registro::factory()->create(['document_number' => '9001002']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9001003',
            'full_name' => 'OMITIDO',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => false,
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.validaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonFragment(['9001001']);

        $this->assertSame('PENDIENTE VALIDACION', $pending->full_name);
    }

    public function test_omit_sets_requires_false_and_needs_edit(): void
    {
        $viewer = $this->userWithMtSt04(['mt_st_04.view', 'view.board.gestion_humana.mt_st_04']);
        $editor = $this->userWithMtSt04(['mt_st_04.view', 'mt_st_04.edit', 'view.board.gestion_humana.mt_st_04']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9002001',
            'full_name' => 'OMITIR ME',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.mt-st-04.validaciones.omit'), [
                'document_number' => '9002001',
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.validaciones.omit'), [
                'document_number' => '9002001',
            ])
            ->assertRedirect();

        $this->assertFalse(
            (bool) EmployeeFichaProfile::query()->where('document_number', '9002001')->value('requires_psicofisicos')
        );
    }

    public function test_enable_sets_requires_true(): void
    {
        $editor = $this->userWithMtSt04(['mt_st_04.view', 'mt_st_04.edit', 'view.board.gestion_humana.mt_st_04']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9003001',
            'full_name' => 'REACTIVAR',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => false,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.validaciones.enable'), [
                'document_number' => '9003001',
            ])
            ->assertRedirect();

        $this->assertTrue(
            (bool) EmployeeFichaProfile::query()->where('document_number', '9003001')->value('requires_psicofisicos')
        );
    }

    public function test_tabs_include_validaciones(): void
    {
        $tabs = config('access.mt_st_04_tabs');

        $this->assertSame('Validaciones', $tabs['validaciones']);
    }

    public function test_viewer_can_export_validaciones_respecting_filters(): void
    {
        $viewer = $this->userWithMtSt04(['mt_st_04.view', 'view.board.gestion_humana.mt_st_04']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9005001',
            'full_name' => 'EXPORT ANA',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
            'position_name' => 'SUPERVISOR',
            'residence_city_name' => 'CALI',
            'cost_center_name' => 'CC-01',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '9005002',
            'full_name' => 'EXPORT LUIS',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
            'position_name' => 'GUARDA',
            'residence_city_name' => 'BOGOTA',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.validaciones.export', [
                'ciudad' => 'CALI',
            ]))
            ->assertOk();

        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type'),
        );

        $tmp = tempnam(sys_get_temp_dir(), 'mtst04val');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $response->streamedContent());

        $sheet = IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('CEDULA', (string) $sheet->getCell([1, 2])->getValue());
        $this->assertSame('9005001', (string) $sheet->getCell([1, 3])->getValue());
        $this->assertSame('EXPORT ANA', (string) $sheet->getCell([2, 3])->getValue());
        $this->assertSame('SUPERVISOR', (string) $sheet->getCell([3, 3])->getValue());
        $this->assertSame('CALI', (string) $sheet->getCell([4, 3])->getValue());
        $this->assertSame('CC-01', (string) $sheet->getCell([5, 3])->getValue());
        $this->assertSame('SI', (string) $sheet->getCell([6, 3])->getValue());
        $this->assertSame('', (string) $sheet->getCell([1, 4])->getValue());
    }

    public function test_validaciones_filters_by_ciudad_and_cargo(): void
    {
        $viewer = $this->userWithMtSt04(['mt_st_04.view', 'view.board.gestion_humana.mt_st_04']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '9004001',
            'full_name' => 'ANA CALI SUPER',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
            'position_name' => 'SUPERVISOR',
            'residence_city_name' => 'CALI',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '9004002',
            'full_name' => 'LUIS BOGOTA GUARDA',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_psicofisicos' => true,
            'position_name' => 'GUARDA',
            'residence_city_name' => 'BOGOTA',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.validaciones'))
            ->assertOk()
            ->assertSee('id="filter_ciudad"', false)
            ->assertSee('id="filter_cargo"', false);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.validaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'ciudad' => 'CALI',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('ANA CALI SUPER', false)
            ->assertDontSee('LUIS BOGOTA GUARDA', false);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.validaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'cargo' => 'GUARDA',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('LUIS BOGOTA GUARDA', false)
            ->assertDontSee('ANA CALI SUPER', false);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithMtSt04(array $permissions): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo($permissions);

        return $user;
    }
}
