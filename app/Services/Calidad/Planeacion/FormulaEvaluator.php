<?php

namespace App\Services\Calidad\Planeacion;

use DomainException;
use InvalidArgumentException;

/**
 * Evalúa fórmulas de indicadores con lista blanca: números, claves, + - * / ( ) y menos unario.
 */
final class FormulaEvaluator
{
    private const T_NUMBER = 'number';

    private const T_IDENT = 'ident';

    private const T_OP = 'op';

    private const T_LPAREN = 'lparen';

    private const T_RPAREN = 'rparen';

    /**
     * @var array<string, array{prec: int, assoc: string}>
     */
    private const OPERATORS = [
        'u-' => ['prec' => 4, 'assoc' => 'right'],
        '*' => ['prec' => 3, 'assoc' => 'left'],
        '/' => ['prec' => 3, 'assoc' => 'left'],
        '+' => ['prec' => 2, 'assoc' => 'left'],
        '-' => ['prec' => 2, 'assoc' => 'left'],
    ];

    /**
     * @param  list<string>  $allowedKeys
     * @return list<string>
     */
    public function validate(string $formula, array $allowedKeys): array
    {
        $formula = trim($formula);

        if ($formula === '') {
            return ['La fórmula está vacía.'];
        }

        try {
            $tokens = $this->tokenize($formula);
        } catch (InvalidArgumentException $e) {
            return [$e->getMessage()];
        }

        $errors = [];
        $allowed = array_fill_keys($allowedKeys, true);

        foreach ($tokens as $token) {
            if ($token['type'] === self::T_IDENT && ! isset($allowed[$token['value']])) {
                $errors[] = 'La clave «'.$token['value'].'» no existe en los campos.';
            }
        }

        try {
            $this->toRpn($tokens);
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, int|float|string>  $values
     */
    public function evaluate(string $formula, array $values): float
    {
        $tokens = $this->tokenize($formula);
        $rpn = $this->toRpn($tokens);

        return $this->evalRpn($rpn, $values);
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function displayWithLabels(string $formula, array $labels): string
    {
        try {
            $tokens = $this->tokenize($formula);
        } catch (InvalidArgumentException) {
            return $formula;
        }

        $out = '';

        foreach ($tokens as $index => $token) {
            $piece = match ($token['type']) {
                self::T_IDENT => $labels[$token['value']] ?? $token['value'],
                self::T_NUMBER => $token['value'],
                self::T_LPAREN => '(',
                self::T_RPAREN => ')',
                self::T_OP => $token['value'] === 'u-' ? '-' : $token['value'],
                default => $token['value'],
            };

            $prev = $tokens[$index - 1] ?? null;
            $needSpace = $index > 0
                && $token['type'] !== self::T_RPAREN
                && ($prev['type'] ?? null) !== self::T_LPAREN
                && ! ($token['type'] === self::T_OP && $token['value'] === 'u-');

            if ($needSpace) {
                $out .= ' ';
            }

            $out .= $piece;
        }

        return $out;
    }

    /**
     * @return list<array{type: string, value: string}>
     */
    private function tokenize(string $formula): array
    {
        $tokens = [];
        $length = strlen($formula);
        $i = 0;

        while ($i < $length) {
            $char = $formula[$i];

            if (ctype_space($char)) {
                $i++;

                continue;
            }

            if (ctype_digit($char) || ($char === '.' && $i + 1 < $length && ctype_digit($formula[$i + 1]))) {
                $start = $i;
                $i++;
                while ($i < $length && (ctype_digit($formula[$i]) || $formula[$i] === '.')) {
                    $i++;
                }
                $raw = substr($formula, $start, $i - $start);
                if (substr_count($raw, '.') > 1 || $raw === '.') {
                    throw new InvalidArgumentException('La fórmula tiene un número mal formado.');
                }
                $tokens[] = ['type' => self::T_NUMBER, 'value' => $raw];

                continue;
            }

            if (ctype_lower($char)) {
                $start = $i;
                $i++;
                while ($i < $length && (ctype_lower($formula[$i]) || ctype_digit($formula[$i]) || $formula[$i] === '_')) {
                    $i++;
                }
                $tokens[] = ['type' => self::T_IDENT, 'value' => substr($formula, $start, $i - $start)];

                continue;
            }

            if ($char === '(') {
                $tokens[] = ['type' => self::T_LPAREN, 'value' => '('];
                $i++;

                continue;
            }

            if ($char === ')') {
                $tokens[] = ['type' => self::T_RPAREN, 'value' => ')'];
                $i++;

                continue;
            }

            if (in_array($char, ['+', '-', '*', '/'], true)) {
                $prev = $tokens[array_key_last($tokens)] ?? null;
                $isUnaryMinus = $char === '-' && (
                    $prev === null
                    || in_array($prev['type'], [self::T_OP, self::T_LPAREN], true)
                );

                $tokens[] = [
                    'type' => self::T_OP,
                    'value' => $isUnaryMinus ? 'u-' : $char,
                ];
                $i++;

                continue;
            }

            throw new InvalidArgumentException('La fórmula tiene un carácter no permitido.');
        }

        if ($tokens === []) {
            throw new InvalidArgumentException('La fórmula está vacía.');
        }

        return $tokens;
    }

    /**
     * @param  list<array{type: string, value: string}>  $tokens
     * @return list<array{type: string, value: string}>
     */
    private function toRpn(array $tokens): array
    {
        $output = [];
        $ops = [];

        foreach ($tokens as $token) {
            if ($token['type'] === self::T_NUMBER || $token['type'] === self::T_IDENT) {
                $output[] = $token;

                continue;
            }

            if ($token['type'] === self::T_LPAREN) {
                $ops[] = $token;

                continue;
            }

            if ($token['type'] === self::T_RPAREN) {
                $found = false;
                while ($ops !== []) {
                    $top = array_pop($ops);
                    if ($top['type'] === self::T_LPAREN) {
                        $found = true;
                        break;
                    }
                    $output[] = $top;
                }

                if (! $found) {
                    throw new InvalidArgumentException('Los paréntesis de la fórmula no están balanceados.');
                }

                continue;
            }

            while ($ops !== []) {
                $top = $ops[array_key_last($ops)];
                if ($top['type'] !== self::T_OP) {
                    break;
                }

                $tokenPrec = self::OPERATORS[$token['value']]['prec'];
                $topPrec = self::OPERATORS[$top['value']]['prec'];
                $tokenAssoc = self::OPERATORS[$token['value']]['assoc'];

                $shouldPop = $tokenAssoc === 'left'
                    ? $tokenPrec <= $topPrec
                    : $tokenPrec < $topPrec;

                if (! $shouldPop) {
                    break;
                }

                $output[] = array_pop($ops);
            }

            $ops[] = $token;
        }

        while ($ops !== []) {
            $top = array_pop($ops);
            if ($top['type'] === self::T_LPAREN || $top['type'] === self::T_RPAREN) {
                throw new InvalidArgumentException('Los paréntesis de la fórmula no están balanceados.');
            }
            $output[] = $top;
        }

        if ($output === []) {
            throw new InvalidArgumentException('La fórmula está mal formada.');
        }

        return $output;
    }

    /**
     * @param  list<array{type: string, value: string}>  $rpn
     * @param  array<string, int|float|string>  $values
     */
    private function evalRpn(array $rpn, array $values): float
    {
        $stack = [];

        foreach ($rpn as $token) {
            if ($token['type'] === self::T_NUMBER) {
                $stack[] = (float) $token['value'];

                continue;
            }

            if ($token['type'] === self::T_IDENT) {
                if (! array_key_exists($token['value'], $values)) {
                    throw new InvalidArgumentException('La clave «'.$token['value'].'» no existe en los campos.');
                }

                $stack[] = (float) $values[$token['value']];

                continue;
            }

            if ($token['value'] === 'u-') {
                if ($stack === []) {
                    throw new InvalidArgumentException('La fórmula está mal formada.');
                }
                $stack[] = -1 * array_pop($stack);

                continue;
            }

            if (count($stack) < 2) {
                throw new InvalidArgumentException('La fórmula está mal formada.');
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            $stack[] = match ($token['value']) {
                '+' => $left + $right,
                '-' => $left - $right,
                '*' => $left * $right,
                '/' => $this->divide($left, $right),
                default => throw new InvalidArgumentException('La fórmula tiene un operador no permitido.'),
            };
        }

        if (count($stack) !== 1) {
            throw new InvalidArgumentException('La fórmula está mal formada.');
        }

        return (float) $stack[0];
    }

    private function divide(float $left, float $right): float
    {
        if (abs($right) < 1e-12) {
            throw new DomainException('No se puede dividir entre cero.');
        }

        return $left / $right;
    }
}
