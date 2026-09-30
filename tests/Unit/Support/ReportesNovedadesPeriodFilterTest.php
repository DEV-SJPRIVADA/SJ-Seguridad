<?php

namespace Tests\Unit\Support;

use App\Support\ReportesNovedadesPeriodFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportesNovedadesPeriodFilterTest extends TestCase
{
    public function test_defaults_to_current_month_and_quincena(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 12:00:00'));

        $filters = ReportesNovedadesPeriodFilter::resolveFromRequest(Request::create('/test', 'GET'));

        $this->assertSame('2026-09', $filters['mes']);
        $this->assertSame('2', $filters['quincena']);
        $this->assertTrue($filters['period_active']);
        $this->assertSame('2026-09-16', $filters['period_desde']);
        $this->assertSame('2026-09-30', $filters['period_hasta']);
        $this->assertSame('', $filters['fecha_desde']);
        $this->assertSame('', $filters['fecha_hasta']);

        Carbon::setTestNow();
    }

    public function test_quincena_one_bounds_are_first_through_fifteenth(): void
    {
        [$desde, $hasta] = ReportesNovedadesPeriodFilter::boundsFor('2026-02', 1);

        $this->assertSame('2026-02-01', $desde->toDateString());
        $this->assertSame('2026-02-15', $hasta->toDateString());
    }

    public function test_date_range_takes_precedence_when_either_bound_is_set(): void
    {
        $filters = ReportesNovedadesPeriodFilter::resolveFromRequest(
            Request::create('/test', 'GET', ['fecha_desde' => '2026-08-01', 'mes' => '2026-09', 'quincena' => '1'])
        );

        $this->assertFalse($filters['period_active']);
        $this->assertSame('', $filters['mes']);
        $this->assertSame('', $filters['quincena']);
        $this->assertSame('2026-08-01', $filters['fecha_desde']);
        $this->assertNull($filters['period_desde']);
    }

    public function test_to_query_params_excludes_internal_period_keys(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10'));

        $filters = ReportesNovedadesPeriodFilter::resolveFromRequest(Request::create('/test', 'GET'));
        $params = ReportesNovedadesPeriodFilter::toQueryParams($filters);

        $this->assertSame(['mes' => '2026-09', 'quincena' => '1'], $params);
        $this->assertArrayNotHasKey('period_active', $params);
        $this->assertArrayNotHasKey('period_desde', $params);

        Carbon::setTestNow();
    }
}
