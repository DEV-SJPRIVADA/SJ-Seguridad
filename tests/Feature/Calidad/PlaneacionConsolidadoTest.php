<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use App\Services\Calidad\Planeacion\PlaneacionConsolidadoService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionConsolidadoTest extends TestCase
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

    public function test_consulta_show_todos_recalculates_summed_fields(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $maria = $this->assignedCapturer($indicator, 'María Gómez');
        $carlos = $this->assignedCapturer($indicator, 'Carlos Pérez');

        $this->actingAs($maria)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'input_data' => ['campo_1' => 22, 'campo_2' => 25, 'campo_3' => 0.02],
            ]))
            ->assertRedirect();

        $this->actingAs($carlos)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'input_data' => ['campo_1' => 24, 'campo_2' => 25, 'campo_3' => 0],
            ]))
            ->assertRedirect();

        $todos = $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 4,
            ]))
            ->assertOk();

        $todos->assertSee('FICHA DEL INDICADOR DE GESTIÓN');
        $todos->assertSee('Todos los capturadores');
        $todos->assertSee('>46<', false);
        $todos->assertSee('>50<', false);
        $todos->assertSee('>0.02<', false);
        $todos->assertSee('94.00%');
        $todos->assertSee($maria->name);
        $todos->assertSee($carlos->name);

        $filtered = $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 4,
                'capturador_id' => $maria->id,
            ]))
            ->assertOk();

        $filtered->assertSee('>22<', false);
        $filtered->assertSee('>0.02<', false);
        $filtered->assertSee('90.00%');
        $filtered->assertDontSee('94.00%');
        $filtered->assertDontSee('>46<', false);
    }

    public function test_director_does_not_see_capturer_from_another_indicator(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $own = $this->publishedIndicator('IND-OWN');
        $other = $this->publishedIndicator('IND-OTH');
        $maria = $this->assignedCapturer($own, 'María Gómez');
        $pedro = $this->assignedCapturer($other, 'Pedro Ruiz');
        $director = $this->assignedConsultant($own, 'Director Equipo');

        $this->actingAs($maria)
            ->post(route('calidad.planeacion.captura.store', $own), $this->capturePayload())
            ->assertRedirect();

        $response = $this->actingAs($director)
            ->get(route('calidad.planeacion.indicadores.show', [
                'indicator' => $own,
                'year' => 2026,
                'month' => 4,
                'capturador_id' => $pedro->id,
            ]))
            ->assertOk();

        $response->assertSee($maria->name);
        $response->assertDontSee($pedro->name);
        $response->assertSee('90.00%');
    }

    public function test_capturer_without_consult_sees_own_ficha_without_selector(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator, 'Ana Captura');
        $other = $this->assignedCapturer($indicator, 'Luis Otro');

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $this->actingAs($other)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'input_data' => ['campo_1' => 24, 'campo_2' => 25, 'campo_3' => 0],
            ]))
            ->assertRedirect();

        $response = $this->actingAs($capturer)
            ->get(route('calidad.planeacion.captura.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 4,
            ]))
            ->assertOk();

        $response->assertSee('FICHA DEL INDICADOR DE GESTIÓN');
        $response->assertSee('90.00%');
        $response->assertDontSee('Todos los capturadores');
        $response->assertDontSee('name="capturador_id"', false);
        $response->assertDontSee('94.00%');
        $response->assertDontSee($other->name);
    }

    public function test_chart_payload_keeps_november_and_december_on_their_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $maria = $this->assignedCapturer($indicator, 'María Gómez');

        $this->actingAs($maria)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'month' => 11,
                'input_data' => ['campo_1' => 22, 'campo_2' => 25, 'campo_3' => 0.02],
            ]))
            ->assertRedirect();

        $this->travelTo(Carbon::parse('2026-12-10 09:00:00'));
        $this->actingAs($maria)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'month' => 12,
                'input_data' => ['campo_1' => 24, 'campo_2' => 25, 'campo_3' => 0],
            ]))
            ->assertRedirect();

        $ficha = app(PlaneacionConsolidadoService::class)->ficha(
            $indicator->fresh(['versions', 'currentPublishedVersion', 'assignees.user']),
            $this->editor,
            2026,
            12,
            (int) $maria->id,
        );

        $this->assertSame(array_values(PlaneacionConsolidadoService::MONTH_ABBREV), $ficha['chart']['months']);
        $this->assertNull($ficha['chart']['result'][9]);
        $this->assertSame(90.0, $ficha['chart']['result'][10]);
        $this->assertSame(96.0, $ficha['chart']['result'][11]);
        $this->assertSame(22.0, $ficha['chart']['fields'][0]['data'][10]);
        $this->assertSame(24.0, $ficha['chart']['fields'][0]['data'][11]);
    }

    public function test_can_consult_superseded_version_and_see_changes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $v1 = $indicator->currentPublishedVersion;
        $this->assertNotNull($v1);

        $this->actingAs($this->editor)
            ->patch(route('calidad.planeacion.indicadores.update', $indicator), $this->indicatorPayload([
                'name' => 'eficacia v2',
                'formula' => '((campo_1 / campo_2) + campo_3) * 100',
            ]))
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        $indicator->refresh()->load('versions');
        $v1 = $indicator->versions->firstWhere('version_number', 1);
        $this->assertNotNull($v1);
        $this->assertSame(QualityPlanningIndicatorVersion::STATUS_SUPERSEDED, $v1->status);

        $historical = $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 12,
                'version' => $v1->id,
            ]))
            ->assertOk();

        $historical->assertSee('Consultando v1');
        $historical->assertSee('Reemplazado');
        $historical->assertSee('EFICACIA DEL PROCESO');
        $historical->assertSee('Nombre: EFICACIA DEL PROCESO → EFICACIA V2');
        $historical->assertSee('Historial de versiones');

        $current = $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 12,
            ]))
            ->assertOk();

        $current->assertSee('EFICACIA V2');
        $current->assertSee('Vigente (según el mes)');
        $current->assertDontSee('Consultando v1');

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.edit', $indicator))
            ->assertOk()
            ->assertSee('Historial de versiones')
            ->assertSee('Nombre: EFICACIA DEL PROCESO → EFICACIA V2');
    }

    private function publishedIndicator(string $code = 'IND-001'): QualityPlanningIndicator
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->indicatorPayload([
                'code' => $code,
            ]))
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->where('code', $code)->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        return $indicator->fresh(['versions', 'currentPublishedVersion', 'assignees']);
    }

    private function assignedCapturer(QualityPlanningIndicator $indicator, string $name): User
    {
        return $this->shareUser($indicator, $name, false);
    }

    private function assignedConsultant(QualityPlanningIndicator $indicator, string $name): User
    {
        return $this->shareUser($indicator, $name, true);
    }

    private function shareUser(QualityPlanningIndicator $indicator, string $name, bool $canConsult): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'must_change_password' => false,
        ]);
        $assignees = $indicator->assignees()
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->user_id,
                'can_consult' => (bool) $row->can_consult,
            ])
            ->all();
        $assignees[] = ['user_id' => $user->id, 'can_consult' => $canConsult];

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => $assignees,
            ])
            ->assertRedirect();

        $indicator->load('assignees');

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function capturePayload(array $override = []): array
    {
        return array_merge([
            'year' => 2026,
            'month' => 4,
            'input_data' => [
                'campo_1' => 22,
                'campo_2' => 25,
                'campo_3' => 0.02,
            ],
            'analysis_text' => 'cumplimiento del mes',
        ], $override);
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
                ['key' => 'campo_1', 'label' => 'numerador', 'decimals' => 2, 'required' => '1'],
                ['key' => 'campo_2', 'label' => 'denominador', 'decimals' => 2, 'required' => '1'],
                ['key' => 'campo_3', 'label' => 'factor', 'decimals' => 2, 'required' => '1'],
            ],
        ], $override);
    }
}
