<?php

namespace Tests\Unit\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Services\Calidad\Planeacion\PlaneacionPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionPeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlaneacionPeriodService $periods;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periods = new PlaneacionPeriodService;
    }

    public function test_v1_applies_from_publish_month_inclusive(): void
    {
        $indicator = $this->indicatorWithVersions([
            ['number' => 1, 'at' => '2026-02-10 09:00:00', 'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED],
        ]);

        $january = $this->periods->versionForCapture($indicator, 2026, 1);
        $this->assertFalse($january->isCapturable());
        $this->assertFalse($january->closed);
        $this->assertStringContainsString('enero 2026', $january->message);

        $february = $this->periods->versionForCapture($indicator, 2026, 2);
        $this->assertTrue($february->isCapturable());
        $this->assertSame(1, $february->version?->version_number);
    }

    public function test_publishing_v2_in_march_closes_march_keeps_february_and_april_uses_v2(): void
    {
        $indicator = $this->indicatorWithVersions([
            ['number' => 1, 'at' => '2026-01-10 09:00:00', 'status' => QualityPlanningIndicatorVersion::STATUS_SUPERSEDED],
            ['number' => 2, 'at' => '2026-03-15 12:00:00', 'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED],
        ]);

        $february = $this->periods->versionForCapture($indicator, 2026, 2);
        $this->assertTrue($february->isCapturable());
        $this->assertSame(1, $february->version?->version_number);

        $march = $this->periods->versionForCapture($indicator, 2026, 3);
        $this->assertFalse($march->isCapturable());
        $this->assertTrue($march->closed);
        $this->assertStringContainsString('marzo 2026', $march->message);

        $april = $this->periods->versionForCapture($indicator, 2026, 4);
        $this->assertTrue($april->isCapturable());
        $this->assertSame(2, $april->version?->version_number);
    }

    /**
     * @param  list<array{number: int, at: string, status: string}>  $versions
     */
    private function indicatorWithVersions(array $versions): QualityPlanningIndicator
    {
        $indicator = QualityPlanningIndicator::factory()->create();
        $currentId = null;

        foreach ($versions as $row) {
            $version = QualityPlanningIndicatorVersion::factory()->create([
                'indicator_id' => $indicator->id,
                'version_number' => $row['number'],
                'status' => $row['status'],
                'published_at' => Carbon::parse($row['at']),
            ]);

            if ($row['status'] === QualityPlanningIndicatorVersion::STATUS_PUBLISHED) {
                $currentId = $version->id;
            }
        }

        if ($currentId !== null) {
            $indicator->forceFill(['current_published_version_id' => $currentId])->save();
        }

        return $indicator->fresh(['versions', 'currentPublishedVersion']);
    }
}
