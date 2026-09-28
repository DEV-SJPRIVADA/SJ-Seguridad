<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionExportApoRun;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_viewer_sees_dashboard_kpis_and_recent_runs(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->createActiveFicha('9001002001');

        AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => '9001002001',
            'cargo_apo' => 'ESCOLTA',
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001002002',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);
        AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => '9001002001',
            'cargo_apo' => 'SUPERVISOR',
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001002003',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_DESACREDITADO,
        ]);

        $run = AcreditacionExportApoRun::factory()->create([
            'export_date' => Carbon::now('America/Bogota')->toDateString(),
            'seq' => 1,
            'file_name' => 'APO900576718620260928001.xls',
            'user_id' => $editor->id,
            'rows_exported' => 3,
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('EN PROCESO', false)
            ->assertSee('ACREDITADO', false)
            ->assertSee('POR VENCER', false)
            ->assertSee('DESACREDITADO', false)
            ->assertSee('Candidatos exportables', false)
            ->assertSee('Últimas corridas Export Apo', false)
            ->assertSee($run->file_name, false)
            ->assertDontSee('Próximamente', false)
            ->assertDontSee('select2', false)
            ->assertDontSee('excelHtml5', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('Select2', $html);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics'))
            ->assertOk()
            ->assertJsonPath('kpis.en_proceso', 1)
            ->assertJsonPath('kpis.acreditado', 1)
            ->assertJsonPath('kpis.por_vencer', 1)
            ->assertJsonPath('kpis.desacreditado', 1)
            ->assertJsonPath('kpis.candidatos', 2)
            ->assertJsonFragment([
                'file_name' => $run->file_name,
                'rows_exported' => 3,
                'user_name' => $editor->name,
            ]);
    }

    public function test_editor_can_access_dashboard_and_metrics(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Últimas corridas Export Apo', false)
            ->assertDontSee('Próximamente', false)
            ->assertDontSee('excelHtml5', false);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics'))
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'en_proceso',
                    'acreditado',
                    'por_vencer',
                    'desacreditado',
                    'candidatos',
                    'con_novedad_blanda',
                ],
                'recent_runs',
                'labels',
            ]);
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics'))
            ->assertForbidden();
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

    private function createActiveFicha(string $documentNumber): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $documentNumber,
            'full_name' => 'Ana Maria Lopez Ruiz',
            'first_name' => 'Ana',
            'second_name' => 'Maria',
            'first_surname' => 'Lopez',
            'second_surname' => 'Ruiz',
            'birth_date' => '1990-01-15',
            'sex' => 'F',
            'hire_date' => '2020-03-01',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
    }
}
