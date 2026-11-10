<?php

namespace Tests\Unit\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorAssignee;
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

    public function test_todos_sums_fields_and_recalculates_formula(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        [$indicator, $maria, $carlos] = $this->indicatorWithTwoCaptures();

        $service = app(PlaneacionConsolidadoService::class);
        $todos = $service->monthSnapshot($indicator, 2026, 4, null);

        $this->assertTrue($todos['has_data']);
        $this->assertEqualsWithDelta(46.0, (float) $todos['fields']['campo_1'], 0.0001);
        $this->assertEqualsWithDelta(50.0, (float) $todos['fields']['campo_2'], 0.0001);
        $this->assertEqualsWithDelta(0.02, (float) $todos['fields']['campo_3'], 0.0001);
        $this->assertEqualsWithDelta(94.0, (float) $todos['result'], 0.0001);
        $this->assertTrue($todos['complies']);

        $mariaOnly = $service->monthSnapshot($indicator, 2026, 4, $maria->id);
        $this->assertEqualsWithDelta(22.0, (float) $mariaOnly['fields']['campo_1'], 0.0001);
        $this->assertEqualsWithDelta(25.0, (float) $mariaOnly['fields']['campo_2'], 0.0001);
        $this->assertEqualsWithDelta(0.02, (float) $mariaOnly['fields']['campo_3'], 0.0001);
        $this->assertEqualsWithDelta(90.0, (float) $mariaOnly['result'], 0.0001);

        $carlosOnly = $service->monthSnapshot($indicator, 2026, 4, $carlos->id);
        $this->assertEqualsWithDelta(24.0, (float) $carlosOnly['fields']['campo_1'], 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $carlosOnly['fields']['campo_3'], 0.0001);
        $this->assertEqualsWithDelta(96.0, (float) $carlosOnly['result'], 0.0001);
    }

    public function test_director_filter_does_not_include_capturer_from_another_indicator(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        [$own, $maria] = $this->publishedIndicatorWithCapturer('IND-A', 'María Gómez');
        [$other, $pedro] = $this->publishedIndicatorWithCapturer('IND-B', 'Pedro Ruiz');

        $this->seed(RoleAndPermissionSeeder::class);
        $director = User::factory()->create([
            'name' => 'Director Propio',
            'must_change_password' => false,
        ]);
        $director->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.view',
            'planeacion.dashboard.view',
        ]);
        QualityPlanningIndicatorAssignee::query()->create([
            'indicator_id' => $own->id,
            'user_id' => $director->id,
            'can_capture' => true,
            'can_consult' => true,
        ]);
        $own->load('assignees.user');

        $service = app(PlaneacionConsolidadoService::class);
        $filter = $service->resolveCapturadorFilter($own->fresh(['assignees.user']), $director, $pedro->id);

        $labels = collect($filter['options'])->pluck('label')->all();
        $this->assertContains($maria->name, $labels);
        $this->assertNotContains($pedro->name, $labels);
        $this->assertNull($filter['capturador_id']);

        $this->assertTrue($service->visibleCapturers($other->fresh(['assignees.user']), $director)->isEmpty());
    }

    /**
     * @return array{0: QualityPlanningIndicator, 1: User, 2: User}
     */
    private function indicatorWithTwoCaptures(): array
    {
        [$indicator, $maria] = $this->publishedIndicatorWithCapturer('IND-001', 'María Gómez');
        $carlos = User::factory()->create([
            'name' => 'Carlos Pérez',
            'must_change_password' => false,
        ]);
        QualityPlanningIndicatorAssignee::query()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $carlos->id,
            'can_capture' => true,
            'can_consult' => false,
        ]);

        $version = $indicator->currentPublishedVersion;
        QualityPlanningCapture::factory()->create([
            'indicator_version_id' => $version->id,
            'user_id' => $maria->id,
            'year' => 2026,
            'month' => 4,
            'input_data' => ['campo_1' => 22, 'campo_2' => 25, 'campo_3' => 0.02],
            'result_value' => 90,
            'complies' => true,
        ]);
        QualityPlanningCapture::factory()->create([
            'indicator_version_id' => $version->id,
            'user_id' => $carlos->id,
            'year' => 2026,
            'month' => 4,
            'input_data' => ['campo_1' => 24, 'campo_2' => 25, 'campo_3' => 0],
            'result_value' => 96,
            'complies' => true,
        ]);

        return [$indicator->fresh(['versions', 'currentPublishedVersion', 'assignees.user']), $maria, $carlos];
    }

    /**
     * @return array{0: QualityPlanningIndicator, 1: User}
     */
    private function publishedIndicatorWithCapturer(string $code, string $capturerName): array
    {
        $indicator = QualityPlanningIndicator::factory()->create(['code' => $code]);
        $version = QualityPlanningIndicatorVersion::factory()->published()->create([
            'indicator_id' => $indicator->id,
            'published_at' => Carbon::parse('2026-01-10 09:00:00'),
        ]);
        $indicator->update(['current_published_version_id' => $version->id]);

        $capturer = User::factory()->create([
            'name' => $capturerName,
            'must_change_password' => false,
        ]);
        QualityPlanningIndicatorAssignee::query()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $capturer->id,
            'can_capture' => true,
            'can_consult' => false,
        ]);

        return [$indicator->fresh(['versions', 'currentPublishedVersion', 'assignees.user']), $capturer];
    }
}
