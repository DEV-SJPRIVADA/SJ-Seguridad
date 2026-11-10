<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionCaptureTest extends TestCase
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

    public function test_two_users_same_month_create_two_rows(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $first = $this->assignedCapturer($indicator);
        $second = $this->assignedCapturer($indicator);

        $this->actingAs($first)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $this->actingAs($second)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $this->assertSame(2, QualityPlanningCapture::query()->count());
        $this->assertNotSame(
            QualityPlanningCapture::query()->where('user_id', $first->id)->value('id'),
            QualityPlanningCapture::query()->where('user_id', $second->id)->value('id'),
        );
    }

    public function test_second_save_without_confirm_returns_422(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator);

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $this->actingAs($capturer)
            ->postJson(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('replace_confirmed');
    }

    public function test_confirmed_replace_overwrites_capture(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator);

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $original = QualityPlanningCapture::query()->firstOrFail();
        $this->assertEqualsWithDelta(90.0, (float) $original->result_value, 0.0001);

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'input_data' => [
                    'campo_1' => 25,
                    'campo_2' => 25,
                    'campo_3' => 0.1,
                ],
                'replace_confirmed' => '1',
            ]))
            ->assertRedirect();

        $this->assertSame(1, QualityPlanningCapture::query()->count());
        $replaced = $original->fresh();
        $this->assertEqualsWithDelta(110.0, (float) $replaced->result_value, 0.0001);
        $this->assertTrue((bool) $replaced->complies);
    }

    public function test_improvement_is_required_when_target_is_not_met(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator);
        $failing = $this->capturePayload([
            'input_data' => [
                'campo_1' => 10,
                'campo_2' => 25,
                'campo_3' => 0,
            ],
            'analysis_text' => 'resultado bajo',
        ]);

        $this->actingAs($capturer)
            ->postJson(route('calidad.planeacion.captura.store', $indicator), $failing)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('improvement_required');

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), array_merge($failing, [
                'improvement_required' => 'hacer seguimiento al proceso',
            ]))
            ->assertRedirect();

        $capture = QualityPlanningCapture::query()->firstOrFail();
        $this->assertFalse((bool) $capture->complies);
        $this->assertSame('RESULTADO BAJO', $capture->analysis_text);
        $this->assertSame('HACER SEGUIMIENTO AL PROCESO', $capture->improvement_required);
    }

    public function test_publishing_v2_on_march_15_closes_march_and_april_uses_v2(): void
    {
        $this->travelTo(Carbon::parse('2026-01-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator);

        $this->travelTo(Carbon::parse('2026-03-15 12:00:00'));
        $this->actingAs($this->editor)
            ->patch(route('calidad.planeacion.indicadores.update', $indicator), $this->indicatorPayload([
                'name' => 'eficacia version dos',
            ]))
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        $this->actingAs($capturer)
            ->postJson(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'year' => 2026,
                'month' => 3,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('month');

        $this->actingAs($capturer)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'year' => 2026,
                'month' => 4,
            ]))
            ->assertRedirect();

        $capture = QualityPlanningCapture::query()->firstOrFail();
        $this->assertSame(4, $capture->month);
        $this->assertSame(2, $capture->version->version_number);
    }

    public function test_division_by_zero_returns_422(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $indicator = $this->publishedIndicator();
        $capturer = $this->assignedCapturer($indicator);

        $this->actingAs($capturer)
            ->postJson(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload([
                'input_data' => [
                    'campo_1' => 10,
                    'campo_2' => 0,
                    'campo_3' => 1,
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('input_data');
    }

    private function publishedIndicator(): QualityPlanningIndicator
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->indicatorPayload())
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        return $indicator->fresh(['versions', 'currentPublishedVersion']);
    }

    private function assignedCapturer(QualityPlanningIndicator $indicator): User
    {
        $capturer = User::factory()->create(['must_change_password' => false]);
        $assignees = $indicator->assignees()
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->user_id,
                'can_consult' => (bool) $row->can_consult,
            ])
            ->all();
        $assignees[] = ['user_id' => $capturer->id, 'can_consult' => false];

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => $assignees,
            ])
            ->assertRedirect();

        $indicator->load('assignees');

        return $capturer->fresh();
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
