<?php

namespace Tests\Unit\Calidad;

use App\Services\Calidad\ServicioClienteBusinessDaysService;
use Carbon\Carbon;
use Tests\TestCase;

class ServicioClienteBusinessDaysServiceTest extends TestCase
{
    private ServicioClienteBusinessDaysService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ServicioClienteBusinessDaysService;
    }

    public function test_inclusive_weekdays_include_both_ends(): void
    {
        $monday = Carbon::parse('2026-11-02');
        $friday = Carbon::parse('2026-11-06');

        $this->assertSame(5, $this->service->countInclusive($monday, $friday));
        $this->assertSame(1, $this->service->countInclusive($monday, $monday));
        $this->assertSame(0, $this->service->countInclusive(Carbon::parse('2026-11-07'), Carbon::parse('2026-11-07')));
        $this->assertNull($this->service->countInclusive($friday, $monday));
        $this->assertNull($this->service->countInclusive($monday, null));
    }

    public function test_plazo_adds_ten_weekdays_after_reception(): void
    {
        $friday = Carbon::parse('2026-11-06');

        $this->assertSame('2026-11-20', $this->service->addBusinessDays($friday, 10)->toDateString());
        $this->assertSame(11, $this->service->countInclusive($friday, Carbon::parse('2026-11-20')));
    }
}
