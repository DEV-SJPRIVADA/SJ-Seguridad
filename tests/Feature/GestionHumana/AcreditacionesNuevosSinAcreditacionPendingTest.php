<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionCargo;
use App\Models\EmployeeAcreditacionPending;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Services\GestionHumana\AcreditacionValidacionesResultStore;
use App\Services\GestionHumana\AcreditacionValidacionesRunnerService;
use App\Services\GestionHumana\EmployeeAcreditacionPendingService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionesNuevosSinAcreditacionPendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-21'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_migrate_leaves_queue_empty_even_with_activos_sin_acreditacion(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '1001',
            'full_name' => 'Activo Sin Acreditacion',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->assertSame(0, app(EmployeeAcreditacionPendingService::class)->countPendingActivos());
        $this->assertDatabaseCount('employee_acreditacion_pending', 0);
    }

    public function test_acreditados_icon_only_for_edit_and_cola_requires_edit(): void
    {
        $activo = EmployeeFichaProfile::query()->create([
            'document_number' => '6001',
            'full_name' => 'En Cola Acr',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeAcreditacionPending::factory()->pending()->create([
            'document_number' => '6001',
            'full_name' => 'En Cola Acr',
            'employee_ficha_profile_id' => $activo->id,
        ]);

        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk()
            ->assertDontSee('cursos-registros-page__nuevos-link', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados', ['cola' => 'nuevos-sin-acreditacion']))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk()
            ->assertSee('cursos-registros-page__nuevos-link', false)
            ->assertSee('title="Nuevos sin acreditación"', false);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.acreditados', ['cola' => 'nuevos-sin-acreditacion']))
            ->assertOk()
            ->assertSee('En Cola Acr', false)
            ->assertSee('6001', false)
            ->assertSee('Nuevo acreditado', false)
            ->assertSee('Omitir', false);
    }

    public function test_omit_sets_requires_acreditacion_false_on_ficha(): void
    {
        $activo = EmployeeFichaProfile::query()->create([
            'document_number' => '7001',
            'full_name' => 'Para Omitir Acr',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_acreditacion' => true,
        ]);
        $pending = EmployeeAcreditacionPending::factory()->pending()->create([
            'document_number' => '7001',
            'full_name' => 'Para Omitir Acr',
            'employee_ficha_profile_id' => $activo->id,
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.pendientes.omit', $pending), [
                'omit_reason' => 'No aplica',
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados', ['cola' => 'nuevos-sin-acreditacion']));

        $activo->refresh();
        $this->assertFalse($activo->requires_acreditacion);
    }

    public function test_store_resolves_pending_and_requires_checkbox(): void
    {
        $editor = $this->editorUser();
        AcreditacionCargo::factory()->create([
            'cargo_apo' => 'VIGILANTE',
            'is_active' => true,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '8001',
            'full_name' => 'Store Acr',
            'position_name' => 'GUARDA',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $pending = EmployeeAcreditacionPending::factory()->pending()->create([
            'document_number' => '8001',
            'full_name' => 'Store Acr',
            'employee_ficha_profile_id' => $profile->id,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), [
                'document_number' => '8001',
                'cargo_apo' => 'VIGILANTE',
                'vigencia_acr' => '2027-01-01',
                'requires_acreditacion' => '1',
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'));

        $pending->refresh();
        $this->assertSame(EmployeeAcreditacionPending::STATUS_RESOLVED, $pending->status);
        $this->assertNotNull($pending->acreditacion_acreditado_id);

        $this->assertDatabaseHas('acreditacion_acreditados', [
            'document_number' => '8001',
            'cargo_apo' => 'VIGILANTE',
        ]);
    }

    public function test_store_with_requires_checkbox_unchecked_sets_flag_false(): void
    {
        $editor = $this->editorUser();
        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '8002',
            'full_name' => 'Sin Check',
            'position_name' => 'GUARDA',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_acreditacion' => true,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.acreditados.store'), [
                'document_number' => '8002',
                'requires_acreditacion' => '0',
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'));

        $profile->refresh();
        $this->assertFalse($profile->requires_acreditacion);
        $this->assertDatabaseMissing('acreditacion_acreditados', [
            'document_number' => '8002',
        ]);
    }

    public function test_validaciones_sin_acreditacion_excludes_when_flag_false(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '9001',
            'full_name' => 'Excluido Validacion',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_acreditacion' => false,
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '9002',
            'full_name' => 'Incluido Validacion',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'requires_acreditacion' => true,
        ]);

        $result = app(AcreditacionValidacionesRunnerService::class)->run('2026-09-21');

        $sinDocs = collect($result['colas'][AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION] ?? [])
            ->pluck('document_number')
            ->all();

        $this->assertNotContains('9001', $sinDocs);
        $this->assertContains('9002', $sinDocs);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
