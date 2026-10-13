<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\ClienteInternoSolicitud;
use App\Models\EmployeeArchiveConsultation;
use App\Models\EmployeeArchiveConsultationItem;
use App\Models\EmployeeFichaProfile;
use App\Models\FormacionRegistro;
use App\Models\GhAreaDashboardConcepto;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Services\Navigation\NavigationResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\GhAreaDashboardConceptoSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GestionHumanaAreaDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 0, 0, 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_and_metrics_require_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_manage_users_bypass_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $this->actingAs($admin)
            ->get(route('gestion-humana.dashboard'))
            ->assertOk();
    }

    public function test_board_redirect_goes_to_area_dashboard(): void
    {
        $viewer = $this->viewerUser();
        $viewer->givePermissionTo('view.dashboard');

        $this->actingAs($viewer)
            ->get(route('dashboard', ['module' => 'gestion_humana', 'board' => 'dashboard']))
            ->assertRedirect(route('gestion-humana.dashboard'));
    }

    public function test_dashboard_renders_title_kpis_and_charts(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard de Gestión Humana – Informe Mensual', false)
            ->assertSee('Personal Activo', false)
            ->assertSee('Ingresos del Mes', false)
            ->assertSee('Desvinculaciones', false)
            ->assertSee('Rotación Mensual %', false)
            ->assertSee('Personal Operativo', false)
            ->assertSee('Acreditaciones', false)
            ->assertSee('Psicofísicos', false)
            ->assertSee('Cliente Interno', false)
            ->assertSee('Formación', false)
            ->assertSee('Archivo', false)
            ->assertSee('Evolución de Personal', false)
            ->assertSee('Ingreso Vs Desvinculaciones', false)
            ->assertSee('Evolución Formación', false)
            ->assertSee('Personal por concepto / área', false)
            ->assertSee('Estado Psicofísicos', false)
            ->assertSee('Estado Acreditación', false)
            ->assertSee('Consultas Archivo', false)
            ->assertSee('gh-area-chart-evolucion-personal', false)
            ->assertSee('dash_ciudad', false)
            ->assertSee('dash_cliente', false)
            ->assertSee('Tableros del area', false)
            ->assertDontSee('select2', false)
            ->assertDontSee('excelHtml5', false);
    }

    public function test_navigation_keeps_gestion_humana_active_on_area_dashboard(): void
    {
        $viewer = $this->viewerUser();
        $viewer->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'view.board.gestion_humana.formacion',
        ]);

        $nav = app(NavigationResolver::class)->resolve($viewer, 'gestion-humana.dashboard');
        $gh = collect($nav['appNavigation'])->firstWhere('key', 'gestion_humana');

        $this->assertNotNull($gh);
        $this->assertTrue($gh['active']);
        $this->assertTrue($nav['currentModuleTabs']->isNotEmpty());
        $this->assertTrue(collect($gh['items'])->contains(
            fn (array $item): bool => ($item['route'] ?? null) === 'gestion-humana.dashboard' && ($item['active'] ?? false)
        ));
        $this->assertSame(route('gestion-humana.dashboard'), collect($gh['items'])->firstWhere('route', 'gestion-humana.dashboard')['url'] ?? null);
    }

    public function test_metrics_payload_keys_and_eom_rotation_operativo(): void
    {
        $viewer = $this->viewerUser();

        // Activo EOM oct-2026 (hire antes, sin baja).
        $this->createFicha('1001', 'Activo Uno', [
            'hire_date' => '2026-01-10',
            'termination_date' => null,
            'position_name' => 'GUARDA',
            'work_city_name' => 'BOGOTA',
            'work_center_name' => 'Cliente Alpha',
        ]);
        // Operativo case-insensitive.
        $this->createFicha('1002', 'Activo Dos', [
            'hire_date' => '2025-06-01',
            'termination_date' => null,
            'position_name' => 'guarda',
            'work_city_name' => 'BOGOTA',
            'cost_center_name' => 'Cliente Beta',
        ]);
        // No operativo (fuera de lista canónica).
        $this->createFicha('1003', 'Admin', [
            'hire_date' => '2025-01-01',
            'termination_date' => null,
            'position_name' => 'ANALISTA',
            'work_city_name' => 'CALI',
            'work_center_name' => 'Cliente Alpha',
        ]);
        // Ingreso del mes.
        $this->createFicha('1004', 'Nuevo', [
            'hire_date' => '2026-10-05',
            'termination_date' => null,
            'position_name' => 'OPERADOR',
            'work_city_name' => 'BOGOTA',
            'work_center_name' => 'Cliente Alpha',
        ]);
        // Desvinculación del mes (sigue contando en flujo; no activo EOM).
        $this->createFicha('1005', 'Baja', [
            'hire_date' => '2024-01-01',
            'termination_date' => '2026-10-12',
            'position_name' => 'GUARDA',
            'work_city_name' => 'BOGOTA',
            'work_center_name' => 'Cliente Alpha',
        ]);
        // Contratado después del EOM → no activo oct.
        $this->createFicha('1006', 'Futuro', [
            'hire_date' => '2026-11-01',
            'termination_date' => null,
            'position_name' => 'GUARDA',
        ]);

        MtSt04Registro::factory()->create([
            'document_number' => '1001',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002',
            'estado_1' => MtSt04Registro::ESTADO_VENCIDO,
        ]);

        AcreditacionAcreditado::factory()->create([
            'document_number' => '1001',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => '1003',
            'estado' => AcreditacionAcreditado::ESTADO_EN_PROCESO,
        ]);

        ClienteInternoSolicitud::factory()->create([
            'anio' => 2026,
            'mes' => 10,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => 2026,
            'mes' => 9,
        ]);

        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'mes' => 10,
            'fecha_inicio' => '2026-10-01',
        ]);
        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'mes' => 10,
            'fecha_inicio' => '2026-10-15',
        ]);

        $archiveUser = $viewer;
        $archiveConsultation = EmployeeArchiveConsultation::query()->create([
            'user_id' => $archiveUser->id,
            'document_numbers' => ['1001', '1002'],
            'consultation_types' => ['juridico', 'gerencia'],
            'documents_requested' => 2,
            'documents_matched' => 2,
            'delivered_to' => 'GH',
            'created_at' => '2026-10-08 10:00:00',
            'updated_at' => '2026-10-08 10:00:00',
        ]);
        // KPI Archivo cuenta ítems (cédulas), no cabeceras de consulta.
        foreach ([['1001', 'Activo Uno'], ['1002', 'Activo Dos']] as [$doc, $name]) {
            EmployeeArchiveConsultationItem::query()->create([
                'employee_archive_consultation_id' => $archiveConsultation->id,
                'document_number' => $doc,
                'full_name' => $name,
                'concept' => 'Consulta prueba',
                'delivered_to' => 'GH',
                'week_of_month' => 2,
                'month_number' => 10,
                'month_label' => 'Octubre',
                'created_at' => '2026-10-08 10:00:00',
                'updated_at' => '2026-10-08 10:00:00',
            ]);
        }

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'filters' => ['anio', 'mes', 'ciudad', 'cargo', 'cliente'],
                'kpis' => [
                    'personal_activo',
                    'ingresos_mes',
                    'desvinculaciones_mes',
                    'rotacion_mensual_pct',
                    'rotacion_display',
                    'personal_operativo',
                    'personal_operativo_pct',
                    'acreditaciones',
                    'psicofisicos',
                    'cliente_interno',
                    'formacion',
                    'archivo',
                ],
                'charts' => [
                    'evolucion_personal' => ['labels', 'data'],
                    'ingresos_vs_desvinculaciones' => ['labels', 'ingresos', 'desvinculaciones'],
                    'evolucion_formacion' => ['labels', 'data'],
                    'personal_por_concepto' => ['labels', 'data'],
                    'estado_psicofisicos' => ['labels', 'data'],
                    'estado_acreditacion' => ['labels', 'data'],
                    'consultas_archivo' => ['labels', 'data'],
                ],
                'meta' => [
                    'activos_mes_actual',
                    'activos_mes_anterior',
                    'eom_date',
                    'filters_parciales_nota',
                ],
            ])
            ->json();

        $this->assertSame('2026-10-31', $metrics['meta']['eom_date']);
        // Activos EOM: 1001, 1002, 1003, 1004 (1005 baja en oct, 1006 hire nov).
        $this->assertSame(4, $metrics['kpis']['personal_activo']);
        $this->assertSame(1, $metrics['kpis']['ingresos_mes']);
        $this->assertSame(1, $metrics['kpis']['desvinculaciones_mes']);
        // Operativos: 1001 GUARDA, 1002 guarda, 1004 OPERADOR (no ANALISTA).
        $this->assertSame(3, $metrics['kpis']['personal_operativo']);
        $this->assertSame(1, $metrics['kpis']['psicofisicos']);
        $this->assertSame(1, $metrics['kpis']['acreditaciones']);
        $this->assertSame(1, $metrics['kpis']['cliente_interno']);
        $this->assertSame(2, $metrics['kpis']['formacion']);
        $this->assertSame(2, $metrics['kpis']['archivo']);
        $this->assertSame(['ACREDITADO', 'EN PROCESO', 'POR VENCER'], $metrics['charts']['estado_acreditacion']['labels']);
        $this->assertSame(['VIGENTE', 'VENCERA', 'VENCIDO'], $metrics['charts']['estado_psicofisicos']['labels']);
        // Pie archivo: 2 ítems × 2 motivos (juridico + gerencia) = suma 4.
        $this->assertCount(2, $metrics['charts']['consultas_archivo']['labels']);
        $this->assertSame(4, array_sum($metrics['charts']['consultas_archivo']['data']));

        // Filtro ciudad (displayCitySql).
        $byCity = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
                'ciudad' => 'BOGOTA',
            ]))
            ->assertOk()
            ->json();
        $this->assertSame(3, $byCity['kpis']['personal_activo']);

        // Filtro cliente: COALESCE work_center → cost_center.
        $byClient = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
                'cliente' => 'Cliente Alpha',
            ]))
            ->assertOk()
            ->json();
        // 1001, 1003, 1004 (work_center Alpha); 1002 usa cost_center Beta.
        $this->assertSame(3, $byClient['kpis']['personal_activo']);

        // CI / Formación / Archivo ignoran ciudad.
        $this->assertSame(1, $byCity['kpis']['cliente_interno']);
        $this->assertSame(2, $byCity['kpis']['formacion']);
        $this->assertSame(2, $byCity['kpis']['archivo']);
    }

    public function test_rotacion_display_em_dash_when_denominator_zero(): void
    {
        $viewer = $this->viewerUser();

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(0, $metrics['meta']['activos_mes_actual']);
        $this->assertSame(0, $metrics['meta']['activos_mes_anterior']);
        $this->assertSame(0.0, (float) $metrics['kpis']['rotacion_mensual_pct']);
        $this->assertSame('—', $metrics['kpis']['rotacion_display']);
    }

    public function test_operative_positions_case_insensitive_exact_match(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('2001', 'Ok Guarda', [
            'hire_date' => '2025-01-01',
            'position_name' => '  Guarda  ',
        ]);
        $this->createFicha('2002', 'Ok Supervisor', [
            'hire_date' => '2025-01-01',
            'position_name' => 'supervisor de puesto',
        ]);
        $this->createFicha('2003', 'No Match', [
            'hire_date' => '2025-01-01',
            'position_name' => 'GUARDA ESPECIAL',
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(3, $metrics['kpis']['personal_activo']);
        $this->assertSame(2, $metrics['kpis']['personal_operativo']);
    }

    public function test_chart_personal_por_concepto_groups_and_otros(): void
    {
        $this->seed(GhAreaDashboardConceptoSeeder::class);
        $viewer = $this->viewerUser();

        $this->createFicha('3001', 'G1', [
            'hire_date' => '2025-01-01',
            'position_name' => 'GUARDA',
        ]);
        $this->createFicha('3002', 'G2', [
            'hire_date' => '2025-01-01',
            'position_name' => 'guarda sj',
        ]);
        $this->createFicha('3003', 'O1', [
            'hire_date' => '2025-01-01',
            'position_name' => 'OPERADOR',
        ]);
        $this->createFicha('3004', 'X1', [
            'hire_date' => '2025-01-01',
            'position_name' => 'CARGO NUEVO SIN MAPA',
        ]);

        $chart = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
            ]))
            ->assertOk()
            ->json('charts.personal_por_concepto');

        $byLabel = array_combine($chart['labels'], $chart['data']);
        $this->assertSame(2, $byLabel['GUARDAS'] ?? 0);
        $this->assertSame(1, $byLabel['MONITOREO'] ?? 0);
        $this->assertSame(1, $byLabel['OTROS'] ?? 0);
        // Orden por volumen: GUARDAS primero.
        $this->assertSame('GUARDAS', $chart['labels'][0]);
    }

    public function test_conceptos_crud_and_assign_with_board_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.dashboard.conceptos.store'), ['name' => 'Prueba Area'])
            ->assertCreated()
            ->assertJsonPath('concepto.name', 'PRUEBA AREA');

        $concepto = GhAreaDashboardConcepto::query()->where('name', 'PRUEBA AREA')->firstOrFail();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.dashboard.conceptos.cargos.store', $concepto), [
                'position_name' => '  Cargo Demo  ',
            ])
            ->assertCreated()
            ->assertJsonPath('cargo.position_key', 'CARGO DEMO');

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.dashboard.conceptos.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'PRUEBA AREA']);

        $cargoId = $concepto->cargos()->firstOrFail()->id;

        $this->actingAs($viewer)
            ->deleteJson(route('gestion-humana.dashboard.concepto-cargos.destroy', $cargoId))
            ->assertOk();

        $this->actingAs($viewer)
            ->deleteJson(route('gestion-humana.dashboard.conceptos.destroy', $concepto))
            ->assertOk();

        $this->assertDatabaseMissing('gh_area_dashboard_conceptos', ['id' => $concepto->id]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createFicha(string $documentNumber, string $fullName, array $extra = []): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'GUARDA',
            'hire_date' => '2025-01-01',
        ], $extra));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.dashboard',
        ]);

        return $user;
    }
}
