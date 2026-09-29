<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
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

    public function test_viewer_sees_dashboard_kpis_filters_and_charts(): void
    {
        $viewer = $this->viewerUser();

        AcreditacionCargo::query()->create([
            'cargo_manager' => 'Escolta',
            'cargo_apo' => 'ESCOLTA',
            'cargo_informe' => 'Escolta',
            'cargo_acreditacion' => 'Escolta',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->createFicha('9001002001');
        $this->createFicha('9001002002');
        $this->createFicha('9001002003');
        $this->createFicha('9001002004');

        AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => '9001002001',
            'cargo_apo' => 'ESCOLTA',
            'fecha_solicitud' => Carbon::parse('2026-03-10'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001002002',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-03-15'),
        ]);
        AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => '9001002003',
            'cargo_apo' => 'ESCOLTA',
            'fecha_solicitud' => Carbon::parse('2026-04-01'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001002004',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_DESACREDITADO,
            'fecha_solicitud' => Carbon::parse('2025-12-01'),
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Fecha solicitud desde', false)
            ->assertSee('Cargo APO', false)
            ->assertSee('Estado ficha', false)
            ->assertSee('Por estado', false)
            ->assertSee('Por cargo APO', false)
            ->assertSee('Tendencia solicitudes', false)
            ->assertSee('Tendencia vencimientos', false)
            ->assertDontSee('Candidatos exportables', false)
            ->assertDontSee('Con novedad blanda', false)
            ->assertDontSee('Últimas corridas Export Apo', false)
            ->assertDontSee('Próximamente', false)
            ->assertDontSee('select2', false)
            ->assertDontSee('excelHtml5', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('Select2', $html);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics', [
                'anio' => 2026,
            ]))
            ->assertOk()
            ->assertJsonPath('kpis.total', 4)
            ->assertJsonPath('kpis.en_proceso', 1)
            ->assertJsonPath('kpis.acreditado', 1)
            ->assertJsonPath('kpis.por_vencer', 1)
            ->assertJsonPath('kpis.desacreditado', 1)
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO)
            ->assertJsonStructure([
                'kpis' => [
                    'total',
                    'en_proceso',
                    'acreditado',
                    'por_vencer',
                    'desacreditado',
                ],
                'charts' => [
                    'by_estado',
                    'by_cargo_apo',
                    'trend',
                    'trend_vencimientos',
                ],
                'filters',
                'labels',
            ])
            ->assertJsonPath('charts.trend.anio', 2026)
            ->assertJsonPath('charts.trend_vencimientos.anio', 2026)
            ->assertJsonMissingPath('kpis.candidatos')
            ->assertJsonMissingPath('charts.by_renovacion')
            ->assertJsonMissingPath('recent_runs');
    }

    public function test_dashboard_defaults_to_active_ficha_employees(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('9001005001', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createFicha('9001005002', EmployeeFichaProfile::STATUS_DESVINCULADO);

        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001005001',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-03-01'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001005002',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-03-02'),
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics'))
            ->assertOk()
            ->assertJsonPath('kpis.total', 1)
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics', [
                'ficha_estado' => 'todos',
            ]))
            ->assertOk()
            ->assertJsonPath('kpis.total', 2)
            ->assertJsonPath('filters.ficha_estado', 'todos');

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics', [
                'ficha_estado' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            ]))
            ->assertOk()
            ->assertJsonPath('kpis.total', 1)
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_DESVINCULADO);
    }

    public function test_dashboard_vencimientos_trend_groups_by_vigencia_acr_month(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('9001004001');
        $this->createFicha('9001004002');
        $this->createFicha('9001004003');
        $this->createFicha('9001004004');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001004001',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-01-10'),
            'vigencia_acr' => Carbon::parse('2026-03-15'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001004002',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-01-12'),
            'vigencia_acr' => Carbon::parse('2026-03-28'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001004003',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
            'fecha_solicitud' => Carbon::parse('2026-02-01'),
            'vigencia_acr' => Carbon::parse('2026-07-01'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001004004',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_EN_PROCESO,
            'fecha_solicitud' => Carbon::parse('2026-02-05'),
            'vigencia_acr' => null,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics', [
                'anio' => 2026,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(2, $metrics['charts']['trend_vencimientos']['data'][2]); // marzo
        $this->assertSame(1, $metrics['charts']['trend_vencimientos']['data'][6]); // julio
        $this->assertSame(0, $metrics['charts']['trend_vencimientos']['data'][0]); // enero
    }

    public function test_dashboard_filters_affect_kpis_and_charts(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('9001003001');
        $this->createFicha('9001003002');
        $this->createFicha('9001003003');

        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001003001',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-02-10'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001003002',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'fecha_solicitud' => Carbon::parse('2026-02-12'),
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '9001003003',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
            'fecha_solicitud' => Carbon::parse('2026-06-01'),
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics', [
                'cargo_apo' => 'ESCOLTA',
                'fecha_desde' => '2026-02-01',
                'fecha_hasta' => '2026-02-28',
                'anio' => 2026,
            ]))
            ->assertOk()
            ->assertJsonPath('kpis.total', 1)
            ->assertJsonPath('kpis.acreditado', 1)
            ->assertJsonPath('kpis.por_vencer', 0)
            ->assertJsonPath('filters.cargo_apo', 'ESCOLTA')
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO)
            ->assertJsonPath('charts.by_cargo_apo.labels.0', 'ESCOLTA')
            ->assertJsonPath('charts.by_cargo_apo.data.0', 1);
    }

    public function test_editor_can_access_dashboard_and_metrics(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Por estado', false)
            ->assertDontSee('Últimas corridas Export Apo', false)
            ->assertDontSee('Próximamente', false)
            ->assertDontSee('excelHtml5', false);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.dashboard.metrics'))
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'total',
                    'en_proceso',
                    'acreditado',
                    'por_vencer',
                    'desacreditado',
                ],
                'charts',
                'filters',
                'labels',
            ])
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO);
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

    private function createFicha(
        string $documentNumber,
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $documentNumber,
            'full_name' => 'Empleado '.$documentNumber,
            'position_name' => 'GUARDA',
            'employment_status' => $employmentStatus,
        ]);
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
