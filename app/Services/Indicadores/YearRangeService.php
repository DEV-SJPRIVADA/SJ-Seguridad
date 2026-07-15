<?php

namespace App\Services\Indicadores;

use App\Models\Period;

class YearRangeService
{
    public function years(): array
    {
        $bounds = $this->bounds();

        return range($bounds['min'], $bounds['max']);
    }

    public function bounds(): array
    {
        $currentYear = (int) now()->year;
        $baseYear = (int) config('indicators.base_year', 2026);
        $futureOffset = (int) config('indicators.future_year_offset', 10);
        $storedMin = (int) (Period::query()->min('year') ?? $baseYear);
        $storedMax = (int) (Period::query()->max('year') ?? $currentYear);

        return [
            'min' => min($baseYear, $storedMin),
            'max' => max($currentYear + $futureOffset, $storedMax),
        ];
    }

    public function normalize(int $requestedYear): int
    {
        $bounds = $this->bounds();
        $defaultYear = max(min((int) now()->year, $bounds['max']), $bounds['min']);

        if ($requestedYear < $bounds['min'] || $requestedYear > $bounds['max']) {
            return $defaultYear;
        }

        return $requestedYear;
    }

    public function validationRules(): array
    {
        $bounds = $this->bounds();

        return [
            'required',
            'integer',
            'min:'.$bounds['min'],
            'max:'.$bounds['max'],
        ];
    }
}
