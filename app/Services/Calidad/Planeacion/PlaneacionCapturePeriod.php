<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;

final class PlaneacionCapturePeriod
{
    public function __construct(
        public readonly ?QualityPlanningIndicatorVersion $version,
        public readonly bool $closed,
        public readonly string $message = '',
    ) {}

    public function isCapturable(): bool
    {
        return $this->version instanceof QualityPlanningIndicatorVersion && ! $this->closed;
    }
}
