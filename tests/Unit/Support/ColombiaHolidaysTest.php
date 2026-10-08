<?php

namespace Tests\Unit\Support;

use App\Support\ColombiaHolidays;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ColombiaHolidaysTest extends TestCase
{
    #[Test]
    public function test_known_holidays_2026_and_easter_week(): void
    {
        $holidays = app(ColombiaHolidays::class);

        // Pascua 2026 = 5 abr → jueves/viernes santo 2–3 abr.
        $this->assertTrue($holidays->isHoliday('2026-04-02'));
        $this->assertTrue($holidays->isHoliday('2026-04-03'));
        $this->assertFalse($holidays->isHoliday('2026-04-04'));

        // Año Nuevo y Navidad fijos.
        $this->assertTrue($holidays->isHoliday('2026-01-01'));
        $this->assertTrue($holidays->isHoliday('2026-12-25'));

        // Reyes Magos 6 ene 2026 (martes) → lunes 12 ene.
        $this->assertTrue($holidays->isHoliday('2026-01-12'));
        $this->assertFalse($holidays->isHoliday('2026-01-06'));
    }

    #[Test]
    public function test_iso_dates_for_years_includes_both_years_at_boundary(): void
    {
        $dates = app(ColombiaHolidays::class)->isoDatesForYears(2025, 2026);

        $this->assertContains('2025-12-25', $dates);
        $this->assertContains('2026-01-01', $dates);
        $this->assertContains('2026-01-12', $dates);
    }
}
