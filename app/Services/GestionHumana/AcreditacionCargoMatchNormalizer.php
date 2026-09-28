<?php

namespace App\Services\GestionHumana;

use Normalizer;

final class AcreditacionCargoMatchNormalizer
{
    /**
     * Normaliza cargo_apo / cargo reporte para igualdad exacta (sin “contiene”).
     */
    public function normalizeCargo(mixed $value): string
    {
        return $this->normalizeMatchString($value);
    }

    /**
     * Cédula: trim + comparación case-insensitive (uppercase multibyte).
     */
    public function normalizeDocument(mixed $value): string
    {
        $string = $this->toString($value);
        $string = trim($string);

        return mb_strtoupper($string, 'UTF-8');
    }

    public function pairKey(mixed $documentNumber, mixed $cargo): string
    {
        return $this->normalizeDocument($documentNumber).'|'.$this->normalizeCargo($cargo);
    }

    public function documentsMatch(mixed $left, mixed $right): bool
    {
        return $this->normalizeDocument($left) === $this->normalizeDocument($right);
    }

    public function cargosMatch(mixed $left, mixed $right): bool
    {
        return $this->normalizeCargo($left) === $this->normalizeCargo($right);
    }

    private function normalizeMatchString(mixed $value): string
    {
        $string = $this->toString($value);
        $string = trim($string);
        $string = preg_replace('/\s+/u', ' ', $string) ?? $string;
        $string = mb_strtoupper($string, 'UTF-8');

        return $this->stripDiacritics($string);
    }

    private function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function stripDiacritics(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($value, Normalizer::FORM_D);
            if (is_string($normalized)) {
                $stripped = preg_replace('/\p{Mn}/u', '', $normalized);

                return is_string($stripped) ? $stripped : $value;
            }
        }

        return strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
            'Ç' => 'C',
        ]);
    }
}
