<?php

namespace Tests\Unit\Support;

use App\Models\FormacionRegistro;
use App\Support\ExportRowLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportRowLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_exceeds_compares_against_max_rows(): void
    {
        FormacionRegistro::factory()->count(3)->create();

        $query = FormacionRegistro::query();

        $this->assertTrue(ExportRowLimit::exceeds($query, 2));
        $this->assertFalse(ExportRowLimit::exceeds($query, 3));
        $this->assertSame(3, ExportRowLimit::count($query));
    }
}
