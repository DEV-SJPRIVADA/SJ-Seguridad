<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
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
        $this->seed(\Database\Seeders\IndicadorSeeder::class);
        $this->seed(\Database\Seeders\DashboardWeightSeeder::class);
        $this->seed(\Database\Seeders\IndicadorDemoDataSeeder::class);
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

        $this->actingAs($user)->get(route('indicadores.dashboard'))->assertOk();
    }

    public function test_operations_capture_user_can_access_captura_list(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
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
            ->assertSee('Periodos de captura');
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

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $allIndicators = \App\Models\Indicator::query()->where('is_active', true)->orderBy('code')->get();

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
        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
        $indicator->update([
            'target_operator' => '<=',
            'target_value' => 90,
        ]);

        $calculator = app(\App\Services\Indicadores\IndicatorMetricCalculator::class);

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
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-02')->firstOrFail();
        $indicator->update(['target_operator' => '<=', 'target_value' => 12.5]);

        $this->actingAs($user)
            ->get(route('indicadores.index'))
            ->assertOk()
            ->assertSee('<= 12.50%');
    }

    public function test_sheet_rows_recalculate_complies_after_operator_change(): void
    {
        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $period = \App\Models\Period::query()->firstOrCreate(
            ['year' => 2026, 'month' => 3],
            ['status' => \App\Models\Period::STATUS_OPEN]
        );

        \App\Models\IndicatorCapture::query()->updateOrCreate(
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
        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-03')->firstOrFail();

        $this->assertTrue($indicator->usesCompositeTarget());
        $this->assertStringContainsString('A ≤', $indicator->metaLabel());
        $this->assertStringContainsString('B ≤', $indicator->metaLabel());
    }

    public function test_capture_form_reflects_updated_meta(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-04')->firstOrFail();
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
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($user)
            ->get(route('indicadores.admin.consolidado.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-01');
    }

    public function test_operations_export_user_can_download_capture_excel(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.capture']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

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

    private function operationsViewer(): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.view']);

        return $user;
    }
}
