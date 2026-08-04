<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Improvement;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use App\Services\Indicadores\IndicatorCaptureAccessService;
use App\Services\Indicadores\IndicatorMetricCalculator;
use App\Support\PermissionCatalog;
use Database\Seeders\DashboardWeightSeeder;
use Database\Seeders\IndicadorDemoDataSeeder;
use Database\Seeders\IndicadorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IndicadorModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        $this->seed(IndicadorSeeder::class);
        $this->seed(DashboardWeightSeeder::class);
        $this->seed(IndicadorDemoDataSeeder::class);
    }

    public function test_guest_cannot_access_indicadores_dashboard(): void
    {
        $this->get(route('indicadores.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_without_operations_permissions_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo('view.dashboard');

        $this->actingAs($user)->get(route('indicadores.dashboard'))->assertForbidden();
    }

    public function test_operations_view_user_can_access_dashboard(): void
    {
        $user = $this->operationsViewer();

        $this->actingAs($user)
            ->get(route('indicadores.dashboard'))
            ->assertOk()
            ->assertSee('KPIs del mes')
            ->assertSee('Mes anterior');
    }

    public function test_operations_capture_user_can_access_captura_list(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false, 'area_key' => 'operaciones']);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $this->actingAs($user)->get(route('indicadores.index'))->assertOk();
    }

    public function test_operations_manage_user_can_access_ajustes(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $this->actingAs($user)
            ->get(route('indicadores.admin.ajustes'))
            ->assertOk()
            ->assertSee('Ajustes de indicadores')
            ->assertSee('Periodos de captura')
            ->assertSee('Capturadores');
    }

    public function test_operations_manage_user_can_access_capturadores_section(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $captureUser = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
            'name' => 'Zona Operaciones Test',
        ]);

        $this->actingAs($manager)
            ->get(route('indicadores.admin.ajustes', ['section' => 'capturadores']))
            ->assertOk()
            ->assertSee('Capturadores de indicadores')
            ->assertSee('Zona Operaciones Test')
            ->assertSee('toggle-switch');
    }

    public function test_operations_manage_user_can_enable_capture_for_operaciones_user(): void
    {
        PermissionCatalog::sync();

        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $captureUser = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.update', $captureUser), [
                'enabled' => true,
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $captureUser->refresh();
        $this->assertTrue($captureUser->can('operations.capture'));
        $this->assertTrue($captureUser->can('operations.view'));
    }

    public function test_operations_manage_user_can_disable_capture_for_operaciones_user(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $captureUser = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $captureUser->givePermissionTo(['operations.capture', 'operations.view']);

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.update', $captureUser), [
                'enabled' => false,
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $captureUser->refresh();
        $this->assertFalse($captureUser->can('operations.capture'));
    }

    public function test_legacy_periodos_route_redirects_to_ajustes(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $this->actingAs($user)
            ->get(route('indicadores.admin.periods.index'))
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'periodos']));
    }

    public function test_legacy_pesos_route_redirects_to_metas(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $this->actingAs($user)
            ->get(route('indicadores.admin.weights'))
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'metas']));
    }

    public function test_operations_manage_user_can_update_metas(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $allIndicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        $payload = [
            'reason' => 'Ajuste anual de metas',
        ];

        foreach ($allIndicators as $item) {
            $payload['operators'][$item->id] = $item->id === $indicator->id ? '<=' : $item->target_operator;
            $payload['metas'][$item->id] = $item->id === $indicator->id ? 95 : (float) $item->target_value;
            $payload['critical'][$item->id] = $item->id === $indicator->id ? 85 : (float) ($item->critical_value ?? 0);
        }

        $this->actingAs($user)
            ->patch(route('indicadores.admin.metas.update'), $payload)
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'metas']));

        $indicator->refresh();
        $this->assertSame('<=', $indicator->target_operator);
        $this->assertSame('95.00', $indicator->target_value);
        $this->assertSame('85.00', $indicator->critical_value);
    }

    public function test_capture_compliance_uses_updated_operator(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
        $indicator->update([
            'target_operator' => '<=',
            'target_value' => 90,
        ]);

        $calculator = app(IndicatorMetricCalculator::class);

        $compliesAt89 = $calculator->calculate($indicator, [
            'supervisiones_programadas' => 100,
            'supervisiones_realizadas' => 89,
        ]);

        $failsAt91 = $calculator->calculate($indicator, [
            'supervisiones_programadas' => 100,
            'supervisiones_realizadas' => 91,
        ]);

        $this->assertTrue($compliesAt89['complies']);
        $this->assertFalse($failsAt91['complies']);
    }

    public function test_capture_list_shows_updated_operator(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false, 'area_key' => 'operaciones']);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-02')->firstOrFail();
        $indicator->update(['target_operator' => '<=', 'target_value' => 12.5]);

        $this->actingAs($user)
            ->get(route('indicadores.index'))
            ->assertOk()
            ->assertSee('<= 12.50%');
    }

    public function test_sheet_rows_recalculate_complies_after_operator_change(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false, 'area_key' => 'operaciones']);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $period = Period::query()->firstOrCreate(
            ['year' => 2026, 'month' => 3],
            ['status' => Period::STATUS_OPEN]
        );

        IndicatorCapture::query()->updateOrCreate(
            [
                'indicator_id' => $indicator->id,
                'user_id' => $user->id,
                'period_id' => $period->id,
            ],
            [
                'input_data' => [
                    'supervisiones_programadas' => 100,
                    'supervisiones_realizadas' => 95,
                ],
                'numerator' => 95,
                'denominator' => 100,
                'result_percentage' => 95,
                'complies' => true,
                'analysis_text' => 'Test',
                'created_by_user_id' => $user->id,
                'updated_by_user_id' => $user->id,
            ]
        );

        $indicator->update(['target_operator' => '<=', 'target_value' => 90]);

        $this->actingAs($user)
            ->get(route('indicadores.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertSee('bg-red-100', false);
    }

    public function test_ft_op_03_meta_label_is_composite(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-03')->firstOrFail();

        $this->assertTrue($indicator->usesCompositeTarget());
        $this->assertStringContainsString('A ≤', $indicator->metaLabel());
        $this->assertStringContainsString('B ≤', $indicator->metaLabel());
    }

    public function test_capture_form_reflects_updated_meta(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false, 'area_key' => 'operaciones']);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
        $indicator->update(['target_value' => 88, 'critical_value' => 75]);

        $this->actingAs($user)
            ->get(route('indicadores.show', ['indicator' => $indicator->code]))
            ->assertOk()
            ->assertSee('88%')
            ->assertSee('75%');
    }

    public function test_dashboard_redirects_to_indicadores_when_board_selected(): void
    {
        $user = $this->operationsViewer();
        Permission::findOrCreate('view.area.operaciones', 'web');
        $user->givePermissionTo('view.area.operaciones');

        $this->actingAs($user)
            ->get(route('dashboard', ['module' => 'operaciones', 'board' => 'indicadores']))
            ->assertRedirect(route('indicadores.dashboard'));
    }

    public function test_operations_manage_user_can_access_consolidado_show(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($user)
            ->get(route('indicadores.admin.consolidado.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-01')
            ->assertSee('Todos los capturadores')
            ->assertSee('FICHA DEL INDICADOR DE GESTION')
            ->assertSee('ft-op-01-chart', false);
    }

    public function test_consolidado_ft_op_02_uses_capture_view(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = Indicator::query()->where('code', 'FT-OP-02')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('indicadores.admin.consolidado.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
            ]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-02')
            ->assertSee('Total servicios')
            ->assertSee('FICHA DEL INDICADOR DE GESTION');
    }

    public function test_consolidado_ft_op_03_uses_capture_view(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = Indicator::query()->where('code', 'FT-OP-03')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('indicadores.admin.consolidado.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
            ]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-03')
            ->assertSee('Facturacion mensual')
            ->assertSee('ft-op-03-chart-finance', false);
    }

    public function test_consolidado_ft_op_09_uses_capture_view(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = Indicator::query()->where('code', 'FT-OP-09')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('indicadores.admin.consolidado.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
            ]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-09')
            ->assertSee('Armas programadas')
            ->assertSee('FICHA DEL INDICADOR DE GESTION');
    }

    public function test_consolidado_ft_op_01_can_filter_by_capturador(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $capturador = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
            'name' => 'Capturador Consolidado Test',
        ]);
        $capturador->givePermissionTo(['operations.capture', 'operations.view']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('indicadores.admin.consolidado.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
                'user_id' => $capturador->id,
            ]))
            ->assertOk()
            ->assertSee('Capturador Consolidado Test')
            ->assertSee('FICHA DEL INDICADOR DE GESTION');
    }

    public function test_operations_export_user_can_download_capture_excel(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $response = $this->actingAs($user)->get(route('indicadores.export.leader.excel', [
            'indicator' => $indicator->code,
            'year' => now()->year,
            'month' => now()->month,
            'user_id' => $user->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_operations_export_user_can_download_management_pptx(): void
    {
        $template = storage_path('app/'.config('indicators.management_report.template'));
        $this->assertFileExists($template, 'La plantilla FO-GI-39 debe existir en storage para exportar PPTX.');

        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.view']);

        $response = $this->actingAs($user)->get(route('indicadores.export.management.pptx', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );
    }

    public function test_management_pptx_uses_demo_capture_values_in_chart(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.view']);

        $response = $this->actingAs($user)->get(route('indicadores.export.management.pptx', [
            'year' => (int) config('indicators.base_year', now()->year),
            'month' => 7,
        ]));

        $response->assertOk();

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);

        $zip = new \ZipArchive;
        $zip->open($file->getPathname());
        $chart = $zip->getFromName('ppt/charts/chart1.xml');
        $zip->close();

        $this->assertNotFalse($chart);
        $this->assertStringNotContainsString('<c:v>737</c:v>', (string) $chart);
        $this->assertStringNotContainsString('formulaRef', (string) $chart);
        $this->assertStringNotContainsString('externalData', (string) $chart);
        $this->assertDoesNotMatchRegularExpression('/<c:extLst>\s*<\/c:extLst>/', (string) $chart);
        $this->assertStringContainsString('<c:v>684</c:v>', (string) $chart);
    }

    public function test_critical_result_detects_below_threshold_for_gte_operator(): void
    {
        $calculator = app(IndicatorMetricCalculator::class);
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $indicator->update(['target_operator' => '>=', 'critical_value' => 60]);

        $this->assertTrue($calculator->isCriticalResult($indicator, 50.0));
        $this->assertFalse($calculator->isCriticalResult($indicator, 85.0));
    }

    public function test_critical_result_detects_above_threshold_for_equals_operator(): void
    {
        $calculator = app(IndicatorMetricCalculator::class);
        $indicator = Indicator::query()->where('code', 'FT-OP-06')->firstOrFail();
        $indicator->update(['target_operator' => '==', 'target_value' => 0, 'critical_value' => 3]);

        $this->assertTrue($calculator->isCriticalResult($indicator, 5.0));
        $this->assertFalse($calculator->isCriticalResult($indicator, 0.0));
    }

    public function test_dashboard_user_ranking_lists_captures_only(): void
    {
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;
        $period = Period::query()->where(['year' => $year, 'month' => $month])->firstOrFail();

        $leaderA = User::factory()->create([
            'name' => 'Ranking Alpha',
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $leaderA->givePermissionTo(['operations.capture', 'operations.view']);

        $leaderB = User::factory()->create([
            'name' => 'Ranking Beta',
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $leaderB->givePermissionTo(['operations.capture', 'operations.view']);

        $inactiveCapturer = User::factory()->create([
            'name' => 'Sin Capturas Mes',
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $inactiveCapturer->givePermissionTo(['operations.capture', 'operations.view']);

        $indicatorOne = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $indicatorTwo = Indicator::query()->where('code', 'FT-OP-02')->firstOrFail();

        $captureAOne = IndicatorCapture::query()->updateOrCreate(
            [
                'indicator_id' => $indicatorOne->id,
                'user_id' => $leaderA->id,
                'period_id' => $period->id,
            ],
            [
                'input_data' => ['total_personal' => 100, 'personal_capacitado' => 90],
                'numerator' => 90,
                'denominator' => 100,
                'result_percentage' => 90,
                'complies' => true,
                'created_by_user_id' => $leaderA->id,
                'updated_by_user_id' => $leaderA->id,
            ]
        );

        IndicatorCapture::query()->updateOrCreate(
            [
                'indicator_id' => $indicatorTwo->id,
                'user_id' => $leaderA->id,
                'period_id' => $period->id,
            ],
            [
                'input_data' => ['total_servicios' => 100, 'no_conformes' => 5],
                'numerator' => 5,
                'denominator' => 100,
                'result_percentage' => 5,
                'complies' => true,
                'created_by_user_id' => $leaderA->id,
                'updated_by_user_id' => $leaderA->id,
            ]
        );

        $captureBOne = IndicatorCapture::query()->updateOrCreate(
            [
                'indicator_id' => $indicatorOne->id,
                'user_id' => $leaderB->id,
                'period_id' => $period->id,
            ],
            [
                'input_data' => ['total_personal' => 100, 'personal_capacitado' => 80],
                'numerator' => 80,
                'denominator' => 100,
                'result_percentage' => 80,
                'complies' => true,
                'created_by_user_id' => $leaderB->id,
                'updated_by_user_id' => $leaderB->id,
            ]
        );

        Improvement::query()->create([
            'indicator_capture_id' => $captureAOne->id,
            'indicator_id' => $indicatorOne->id,
            'user_id' => $leaderA->id,
            'period_id' => $period->id,
            'analysis' => 'Analisis alpha',
            'action_taken' => 'Accion alpha',
            'action_defined' => 'Plan alpha',
            'integrated_analysis_block' => 'Bloque alpha',
            'created_by_user_id' => $leaderA->id,
        ]);

        Improvement::query()->create([
            'indicator_capture_id' => $captureBOne->id,
            'indicator_id' => $indicatorOne->id,
            'user_id' => $leaderB->id,
            'period_id' => $period->id,
            'analysis' => 'Analisis beta',
            'action_taken' => 'Accion beta',
            'action_defined' => 'Plan beta',
            'integrated_analysis_block' => 'Bloque beta',
            'created_by_user_id' => $leaderB->id,
        ]);

        $viewer = $this->operationsViewer();

        $response = $this->actingAs($viewer)
            ->get(route('indicadores.dashboard', ['year' => $year, 'month' => $month]));

        $response->assertOk()
            ->assertSee('Ranking de usuarios')
            ->assertSee('Indicadores gestionados')
            ->assertSee('% gestionado')
            ->assertSee('Mejoras ingresadas')
            ->assertSee('Ranking Alpha')
            ->assertSee('Ranking Beta')
            ->assertDontSee('Sin Capturas Mes');

        $alphaPos = strpos($response->getContent(), 'Ranking Alpha');
        $betaPos = strpos($response->getContent(), 'Ranking Beta');
        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($betaPos);
        $this->assertLessThan($betaPos, $alphaPos);
    }

    public function test_dashboard_shows_critical_indicators_table_with_user_rows(): void
    {
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;
        $captureUser = User::query()->where('email', 'operaciones.demo@sjseguridad.test')->firstOrFail();
        $captureUser->update(['area_key' => 'operaciones']);
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $indicator->update(['target_operator' => '>=', 'critical_value' => 60]);
        $period = Period::query()->where(['year' => $year, 'month' => $month])->firstOrFail();

        IndicatorCapture::query()->updateOrCreate(
            [
                'indicator_id' => $indicator->id,
                'user_id' => $captureUser->id,
                'period_id' => $period->id,
            ],
            [
                'input_data' => ['total_personal' => 100, 'personal_capacitado' => 50],
                'numerator' => 50,
                'denominator' => 100,
                'result_percentage' => 50,
                'complies' => false,
                'created_by_user_id' => $captureUser->id,
                'updated_by_user_id' => $captureUser->id,
            ]
        );

        $viewer = $this->operationsViewer();

        $this->actingAs($viewer)
            ->get(route('indicadores.dashboard', ['year' => $year, 'month' => $month]))
            ->assertOk()
            ->assertSee('Indicadores criticos')
            ->assertSee('Operaciones Demo')
            ->assertSee('FT-OP-01')
            ->assertSee('50.00%');
    }

    public function test_delegate_user_can_access_captura_tab(): void
    {
        $delegate = $this->delegateOnlyUser();

        $this->actingAs($delegate)->get(route('indicadores.index'))->assertOk();
    }

    public function test_delegate_only_user_show_defaults_to_first_capturador(): void
    {
        $this->capturadorUser('Titular Alfabetico');
        $delegate = $this->delegateOnlyUser();

        $expectedFirst = app(IndicatorCaptureAccessService::class)->capturableUsers()->first();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->get(route('indicadores.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('name="capturador_user_id" value="'.$expectedFirst->id.'"', false);
    }

    public function test_delegate_only_user_show_with_valid_capturador_id(): void
    {
        $this->capturadorUser('Titular Uno');
        $titularDos = $this->capturadorUser('Titular Dos');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->get(route('indicadores.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
                'capturador_id' => $titularDos->id,
            ]))
            ->assertOk()
            ->assertSee('name="capturador_user_id" value="'.$titularDos->id.'"', false);
    }

    public function test_show_with_invalid_capturador_id_returns_not_found(): void
    {
        $this->capturadorUser('Titular Valido');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->get(route('indicadores.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
                'capturador_id' => 999999,
            ]))
            ->assertNotFound();
    }

    public function test_delegate_user_can_store_capture_for_titular(): void
    {
        $titular = $this->capturadorUser('Titular Store');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->post(route('indicadores.capture.store', $indicator), array_merge(
                $this->captureStorePayload($titular->id),
                ['year' => 2026, 'month' => 7]
            ))
            ->assertRedirect(route('indicadores.show', [
                'indicator' => $indicator->code,
                'year' => 2026,
                'month' => 7,
                'capturador_id' => $titular->id,
            ]));

        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $titular->id,
        ]);
    }

    public function test_delegated_capture_sets_user_id_titular_and_created_by_actor(): void
    {
        $titular = $this->capturadorUser('Titular Digitador');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload($titular->id),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $capture = IndicatorCapture::query()
            ->where('indicator_id', $indicator->id)
            ->where('user_id', $titular->id)
            ->firstOrFail();

        $this->assertSame($titular->id, $capture->user_id);
        $this->assertSame($delegate->id, $capture->created_by_user_id);
        $this->assertSame($delegate->id, $capture->updated_by_user_id);
    }

    public function test_delegated_capture_audit_includes_delegation_metadata(): void
    {
        $titular = $this->capturadorUser('Titular Auditoria');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload($titular->id),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $log = AuditLog::query()
            ->where('event_type', 'indicator_capture')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue((bool) ($log->metadata['delegated'] ?? false));
        $this->assertSame($titular->id, $log->metadata['titular_user_id'] ?? null);
        $this->assertSame($delegate->id, $log->metadata['actor_user_id'] ?? null);
    }

    public function test_store_with_invalid_capturador_user_id_returns_unprocessable(): void
    {
        $this->capturadorUser('Titular Rango');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->postJson(route('indicadores.capture.store', $indicator), array_merge(
                $this->captureStorePayload(999999),
                ['year' => 2026, 'month' => 7]
            ))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['capturador_user_id']);
    }

    public function test_delegate_only_user_cannot_store_without_capturador_user_id(): void
    {
        $this->capturadorUser('Titular Requerido');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $payload = $this->captureStorePayload(null);
        unset($payload['capturador_user_id']);

        $this->actingAs($delegate)
            ->postJson(route('indicadores.capture.store', $indicator), array_merge($payload, [
                'year' => 2026,
                'month' => 7,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['capturador_user_id']);
    }

    public function test_capture_only_user_self_capture_regression(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($user)
            ->get(route('indicadores.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertDontSee('name="capturador_id"', false);

        $this->actingAs($user)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload(null),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $capture = IndicatorCapture::query()
            ->where('indicator_id', $indicator->id)
            ->where('user_id', $user->id)
            ->where('period_id', Period::query()->where(['year' => 2026, 'month' => 7])->firstOrFail()->id)
            ->firstOrFail();

        $this->assertSame($user->id, $capture->user_id);
        $this->assertSame($user->id, $capture->created_by_user_id);
        $this->assertSame($user->id, $capture->updated_by_user_id);
    }

    public function test_user_with_both_permissions_defaults_to_self_on_show(): void
    {
        $actor = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
            'name' => 'Capturador Ambos Permisos',
        ]);
        $actor->givePermissionTo(array_merge(
            ['view.dashboard', 'operations.capture'],
            app(IndicatorCaptureAccessService::class)->delegatePermissionsToGrant()
        ));

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($actor)
            ->get(route('indicadores.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('name="capturador_user_id" value="'.$actor->id.'"', false);
    }

    public function test_user_with_both_permissions_can_delegate_to_other_capturador(): void
    {
        $actor = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $actor->givePermissionTo(array_merge(
            ['view.dashboard', 'operations.capture'],
            app(IndicatorCaptureAccessService::class)->delegatePermissionsToGrant()
        ));

        $otherTitular = $this->capturadorUser('Otro Titular Delegado');

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($actor)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload($otherTitular->id),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $otherTitular->id,
            'created_by_user_id' => $actor->id,
        ]);
        $this->assertDatabaseMissing('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $actor->id,
        ]);
    }

    public function test_operations_manage_without_delegate_cannot_access_delegate_flow(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $otherCapturador = $this->capturadorUser('Capturador Ignorado');

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($manager)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload($otherCapturador->id),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $manager->id,
        ]);
        $this->assertDatabaseMissing('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $otherCapturador->id,
        ]);
    }

    public function test_operations_manage_user_can_enable_delegate_for_operaciones_user(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $suplente = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.delegate.update', $suplente), [
                'enabled' => true,
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $suplente->refresh();
        $this->assertTrue($suplente->can('operations.capture.delegate'));
        $this->assertTrue($suplente->can('operations.view'));
        $this->assertFalse($suplente->can('operations.capture'));
    }

    public function test_operations_manage_user_can_disable_delegate_for_operaciones_user(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $suplente = $this->delegateOnlyUser();

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.delegate.update', $suplente), [
                'enabled' => false,
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $suplente->refresh();
        $this->assertFalse($suplente->can('operations.capture.delegate'));
    }

    public function test_delegate_toggle_does_not_grant_operations_capture(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $suplente = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);

        $this->actingAs($manager)->patch(route('indicadores.admin.capturadores.delegate.update', $suplente), [
            'enabled' => true,
        ])->assertRedirect();

        $suplente->refresh();
        $this->assertFalse($suplente->can('operations.capture'));
    }

    public function test_delegate_toggle_accepts_string_enabled_from_html_form(): void
    {
        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $suplente = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.delegate.update', $suplente), [
                'enabled' => '1',
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $suplente->refresh();
        $this->assertTrue($suplente->can('operations.capture.delegate'));
    }

    public function test_delegate_toggle_creates_missing_delegate_permission(): void
    {
        Permission::query()->where('name', 'operations.capture.delegate')->delete();

        $manager = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $manager->givePermissionTo(['view.dashboard', 'operations.manage']);

        $suplente = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);

        $this->actingAs($manager)
            ->patch(route('indicadores.admin.capturadores.delegate.update', $suplente), [
                'enabled' => '1',
            ])
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'capturadores']));

        $suplente->refresh();
        $this->assertTrue($suplente->can('operations.capture.delegate'));
        $this->assertDatabaseHas('permissions', ['name' => 'operations.capture.delegate']);
    }

    public function test_delegated_improvement_created_by_reflects_actor(): void
    {
        $titular = $this->capturadorUser('Titular Mejora');
        $delegate = $this->delegateOnlyUser();

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)->post(route('indicadores.capture.store', $indicator), array_merge(
            $this->captureStorePayload($titular->id),
            ['year' => 2026, 'month' => 7]
        ))->assertRedirect();

        $improvement = Improvement::query()
            ->where('indicator_id', $indicator->id)
            ->where('user_id', $titular->id)
            ->firstOrFail();

        $this->assertSame($delegate->id, $improvement->created_by_user_id);
    }

    public function test_closed_period_blocks_delegated_capture(): void
    {
        $titular = $this->capturadorUser('Titular Cerrado');
        $delegate = $this->delegateOnlyUser();

        Period::query()->updateOrCreate(
            ['year' => 2026, 'month' => 7],
            ['status' => Period::STATUS_CLOSED]
        );

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($delegate)
            ->postJson(route('indicadores.capture.store', $indicator), array_merge(
                $this->captureStorePayload($titular->id),
                ['year' => 2026, 'month' => 7]
            ))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    private function delegateOnlyUser(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $user->givePermissionTo(array_merge(
            ['view.dashboard'],
            app(IndicatorCaptureAccessService::class)->delegatePermissionsToGrant()
        ));

        return $user;
    }

    private function capturadorUser(string $name): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'operaciones',
            'name' => $name,
        ]);
        $user->givePermissionTo(['operations.capture', 'operations.view']);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function captureStorePayload(?int $capturadorUserId): array
    {
        $payload = [
            'form' => [
                'total_personal' => 100,
                'personal_capacitado' => 50,
            ],
            'improvement' => [
                'analysis' => 'Analisis de prueba',
                'action_taken' => 'Accion tomada de prueba',
                'action_defined' => 'Accion definida de prueba',
                'improvement_required' => 'Mejora requerida de prueba',
            ],
        ];

        if ($capturadorUserId !== null) {
            $payload['capturador_user_id'] = $capturadorUserId;
        }

        return $payload;
    }

    private function operationsViewer(): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.view']);

        return $user;
    }
}
