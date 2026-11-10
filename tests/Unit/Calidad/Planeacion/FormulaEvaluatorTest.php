<?php

namespace Tests\Unit\Calidad\Planeacion;

use App\Services\Calidad\Planeacion\FormulaEvaluator;
use DomainException;
use Tests\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    private FormulaEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new FormulaEvaluator;
    }

    public function test_example_formula_evaluates_to_ninety(): void
    {
        $formula = '((campo_1 / campo_2) + campo_3) * 100';
        $keys = ['campo_1', 'campo_2', 'campo_3'];

        $this->assertSame([], $this->evaluator->validate($formula, $keys));
        $this->assertEqualsWithDelta(90.0, $this->evaluator->evaluate($formula, [
            'campo_1' => 22,
            'campo_2' => 25,
            'campo_3' => 0.02,
        ]), 0.0001);
    }

    public function test_broken_parentheses_return_spanish_error(): void
    {
        $errors = $this->evaluator->validate('((campo_1 / campo_2) + campo_3 * 100', ['campo_1', 'campo_2', 'campo_3']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('paréntesis', $errors[0]);
    }

    public function test_unknown_key_returns_spanish_error(): void
    {
        $errors = $this->evaluator->validate('campo_1 / extra', ['campo_1']);

        $this->assertContains('La clave «extra» no existe en los campos.', $errors);
    }

    public function test_division_by_zero_throws_domain_exception(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se puede dividir entre cero.');

        $this->evaluator->evaluate('campo_1 / campo_2', [
            'campo_1' => 10,
            'campo_2' => 0,
        ]);
    }

    public function test_unary_minus_and_display_with_labels(): void
    {
        $result = $this->evaluator->evaluate('-campo_1 + 5', ['campo_1' => 2]);

        $this->assertEqualsWithDelta(3.0, $result, 0.0001);
        $this->assertSame(
            '(Numerador / Denominador)',
            $this->evaluator->displayWithLabels('(campo_1 / campo_2)', [
                'campo_1' => 'Numerador',
                'campo_2' => 'Denominador',
            ])
        );
    }
}
