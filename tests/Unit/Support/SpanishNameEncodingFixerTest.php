<?php

namespace Tests\Unit\Support;

use App\Support\SpanishNameEncodingFixer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpanishNameEncodingFixerTest extends TestCase
{
    #[DataProvider('brokenNamesProvider')]
    public function test_repairs_question_marks_in_spanish_names(string $broken, string $expected): void
    {
        $this->assertSame($expected, SpanishNameEncodingFixer::fixPersonName($broken));
    }

    public function test_leaves_clean_names_untouched(): void
    {
        $this->assertSame('MUÑOZ GARCIA', SpanishNameEncodingFixer::fixPersonName('MUÑOZ GARCIA'));
        $this->assertSame('LEÓN', SpanishNameEncodingFixer::fixPersonName('LEÓN'));
    }

    public function test_normalizes_windows1252_bytes_to_utf8(): void
    {
        $raw = 'MU'.chr(0xD1).'OZ';
        $this->assertSame('MUÑOZ', SpanishNameEncodingFixer::fixPersonName($raw));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function brokenNamesProvider(): array
    {
        return [
            'munoz' => ['MU?OZ BUITRON EDUAR ANCIAZAR', 'MUÑOZ BUITRON EDUAR ANCIAZAR'],
            'ordonez' => ['ORDO?EZ PANTOJA HENRRY GIOVANNY', 'ORDOÑEZ PANTOJA HENRRY GIOVANNY'],
            'nanez' => ['?A?EZ HERNANDEZ ANDRES MAURICIO', 'ÑAÑEZ HERNANDEZ ANDRES MAURICIO'],
            'leon' => ['LE?N QUINTANA JORGE ENRIQUE', 'LEÓN QUINTANA JORGE ENRIQUE'],
            'penaloza_patino' => ['PE?ALOZA PATI?O JULIAN ANDRES', 'PEÑALOZA PATIÑO JULIAN ANDRES'],
            'canon' => ['CA?ON', 'CAÑON'],
            'lowercase_enye' => ['Mu?oz', 'Muñoz'],
            'lowercase_leon' => ['Le?n', 'León'],
        ];
    }
}
