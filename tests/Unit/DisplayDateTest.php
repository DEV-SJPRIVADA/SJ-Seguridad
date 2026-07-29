<?php

namespace Tests\Unit;

use App\Support\DisplayDate;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DisplayDateTest extends TestCase
{
    public function test_date_formats_as_dd_mm_yy(): void
    {
        $value = Carbon::parse('2026-07-29');

        $this->assertSame('29/07/26', DisplayDate::date($value));
    }

    public function test_date_returns_empty_placeholder_when_null(): void
    {
        $this->assertSame('—', DisplayDate::date(null));
        $this->assertSame('N/A', DisplayDate::date(null, 'N/A'));
    }

    public function test_date_time_includes_hours(): void
    {
        $value = Carbon::parse('2026-07-29 14:05:00');

        $this->assertSame('29/07/26 14:05', DisplayDate::dateTime($value));
    }
}
