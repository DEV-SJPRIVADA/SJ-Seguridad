<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
