<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorAssignee;
use App\Models\User;
use App\Services\Calidad\Planeacion\PlaneacionAssignmentService;
use App\Services\Calidad\Planeacion\PlaneacionConsolidadoService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionMisIndicadoresTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->editor = User::factory()->create(['must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.edit',
        ]);
    }

    public function test_year_summary_reports_captured_pending_and_last_result(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'María Gómez');
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $this->capture($capturer, $indicator, 2026, 4);

        $indicator = $indicator->fresh(['versions', 'currentPublishedVersion']);
        $summary = app(PlaneacionConsolidadoService::class)
            ->yearSummary([$indicator], $capturer, 2026)[(int) $indicator->id];

        $this->assertSame([4], $summary['captured']);
        $this->assertSame([1, 2, 3], $summary['pending']);
        $this->assertSame(4, $summary['last_month']);
        $this->assertEqualsWithDelta(90.0, (float) $summary['last_result'], 0.0001);
        $this->assertTrue($summary['last_complies']);
    }

    public function test_year_summary_reports_all_days_up_when_every_month_is_captured(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'Ana Captura');
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        foreach ([1, 2, 3, 4] as $month) {
            $this->capture($capturer, $indicator, 2026, $month);
        }

        $indicator = $indicator->fresh(['versions', 'currentPublishedVersion']);
        $summary = app(PlaneacionConsolidadoService::class)
            ->yearSummary([$indicator], $capturer, 2026)[(int) $indicator->id];

        $this->assertSame([1, 2, 3, 4], $summary['captured']);
        $this->assertSame([], $summary['pending']);
    }

    public function test_mis_indicadores_datatable_shows_year_state_and_filters_by_year(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'Luis Captura');
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $this->capture($capturer, $indicator, 2026, 4);

        $json = $this->actingAs($capturer)
            ->getJson(route('calidad.planeacion.captura.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'year' => 2026,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertSame('IND-001', strip_tags((string) $json['data'][0][0]));
        $this->assertStringContainsString('1 de 12', (string) $json['data'][0][3]);
        $this->assertStringContainsString('Pendiente', (string) $json['data'][0][4]);
        $this->assertStringContainsString('ABR', (string) $json['data'][0][5]);
        $this->assertStringContainsString('aria-label="Capturar"', (string) $json['data'][0][6]);
    }

    public function test_previous_year_shows_no_captures_for_current_period(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'Pedro Captura');
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $this->capture($capturer, $indicator, 2026, 4);

        $json = $this->actingAs($capturer)
            ->getJson(route('calidad.planeacion.captura.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'year' => 2025,
            ]))
            ->assertOk()
            ->json();

        $this->assertStringContainsString('0 de 12', (string) $json['data'][0][3]);
    }

    public function test_indicators_tab_shows_capture_shortcut_only_for_editors(): void
    {
        $indicator = $this->publishedIndicator();

        $json = $this->actingAs($this->editor)
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $this->assertStringContainsString('aria-label="Capturar"', (string) $json['data'][0][4]);

        $consultant = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [['user_id' => $consultant->id, 'can_consult' => '1']],
            ])
            ->assertRedirect();

        $json = $this->actingAs($consultant->fresh())
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringNotContainsString('aria-label="Capturar"', (string) $json['data'][0][4]);
    }

    public function test_mis_indicadores_page_renders_year_selector(): void
    {
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'Sofía Captura');

        $this->actingAs($capturer)
            ->get(route('calidad.planeacion.captura', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Mis indicadores')
            ->assertSee('Último resultado')
            ->assertSee('id="mis_indicadores_year"', false);
    }

    private function capture(User $capturer, QualityPlanningIndicator $indicator, int $year, int $month): void
    {
        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), [
                'year' => $year,
                'month' => $month,
                'input_data' => [
                    'campo_1' => 22,
                    'campo_2' => 25,
                    'campo_3' => 0.02,
                ],
                'analysis_text' => 'cumplimiento del mes',
            ])
            ->assertRedirect();
    }

    private function publishedIndicator(string $code = 'IND-001'): QualityPlanningIndicator
    {
        // Publicar en enero para que todo el año tenga versión vigente y sea capturable.
        $this->travelTo(Carbon::parse('2026-01-05 09:00:00'));

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->indicatorPayload(['code' => $code]))
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->where('code', $code)->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        return $indicator->fresh(['versions', 'currentPublishedVersion']);
    }

    private function assignedCapturer(QualityPlanningIndicator $indicator, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'must_change_password' => false,
        ]);

        QualityPlanningIndicatorAssignee::query()->updateOrCreate(
            ['indicator_id' => $indicator->id, 'user_id' => $user->id],
            ['can_capture' => true, 'can_consult' => false],
        );

        app(PlaneacionAssignmentService::class)
            ->grantMinimum($user, false);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function indicatorPayload(array $override = []): array
    {
        return array_merge([
            'code' => 'IND-001',
            'name' => 'eficacia del proceso',
            'objective' => 'medir el cumplimiento',
            'process_key' => 'gestion_gerencial',
            'unit' => 'percentage',
            'frequency' => 'monthly',
            'target_value' => 90,
            'target_operator' => '>=',
            'critical_value' => 70,
            'formula' => '((campo_1 / campo_2) + campo_3) * 100',
            'fields' => [
                ['key' => 'campo_1', 'label' => 'numerador', 'decimals' => 2, 'required' => true],
                ['key' => 'campo_2', 'label' => 'denominador', 'decimals' => 2, 'required' => true],
                ['key' => 'campo_3', 'label' => 'factor', 'decimals' => 2, 'required' => false],
            ],
        ], $override);
    }
}
