<?php

namespace Tests\Unit\Support;

use App\Support\ColombianCurrencyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ColombianCurrencyParserTest extends TestCase
{
    #[DataProvider('salaryProvider')]
    public function test_parse_colombian_currency_values(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, ColombianCurrencyParser::parse($input));
    }

    /**
     * @return array<string, array{0: mixed, 1: ?float}>
     */
    public static function salaryProvider(): array
    {
        return [
            'plain integer' => [2180000, 2180000.0],
            'db decimal string' => ['2180000.00', 2180000.0],
            'formatted thousands' => ['2.180.000', 2180000.0],
            'with currency symbol' => ['$ 2.180.000', 2180000.0],
            'empty string' => ['', null],
            'null' => [null, null],
        ];
    }
}
