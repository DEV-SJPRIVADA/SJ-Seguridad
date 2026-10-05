<?php

namespace Tests\Unit;

use App\Support\SpanishMoneyWords;
use Tests\TestCase;

class SpanishMoneyWordsTest extends TestCase
{
    public function test_converts_salary_to_uppercase_spanish_pesos(): void
    {
        if (! class_exists(\NumberFormatter::class)) {
            $this->markTestSkipped('ext-intl no disponible.');
        }

        $text = SpanishMoneyWords::pesos(1500000);

        $this->assertNotSame('', $text);
        $this->assertStringContainsString('PESOS', $text);
        $this->assertSame(mb_strtoupper($text, 'UTF-8'), $text);
        $this->assertMatchesRegularExpression('/MILL[OÓ]N/u', $text);
    }

    public function test_empty_amount_returns_empty_string(): void
    {
        $this->assertSame('', SpanishMoneyWords::pesos(null));
        $this->assertSame('', SpanishMoneyWords::pesos(''));
    }

    public function test_zero_returns_cero_pesos(): void
    {
        $this->assertSame('CERO PESOS', SpanishMoneyWords::pesos(0));
    }
}
