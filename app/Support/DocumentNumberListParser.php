<?php

namespace App\Support;

/**
 * Parsea listas de cédulas (coma, salto de línea o punto y coma) sin persistir.
 * Match exacto por valor normalizado (solo dígitos cuando aplica).
 */
final class DocumentNumberListParser
{
    public const MAX = 500;

    /**
     * @return list<string> Valores únicos (preferencia a la forma ingresada), máx. {@see MAX}
     */
    public function parse(string $raw, int $max = self::MAX): array
    {
        $max = max(1, $max);
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $documents = [];

        foreach ($parts as $part) {
            $document = trim((string) $part);
            if ($document === '') {
                continue;
            }

            $normalized = $this->normalize($document);
            if ($normalized === '') {
                continue;
            }

            if (! isset($documents[$normalized])) {
                $documents[$normalized] = $document;
            }

            if (count($documents) >= $max) {
                break;
            }
        }

        return array_values($documents);
    }

    /**
     * @param  mixed  $input  string, list<string>|null
     * @return list<string>
     */
    public function fromInput(mixed $input, int $max = self::MAX): array
    {
        if (is_array($input)) {
            $raw = implode(',', array_map(
                static fn ($item): string => trim((string) $item),
                $input,
            ));
        } else {
            $raw = trim((string) ($input ?? ''));
        }

        if ($raw === '') {
            return [];
        }

        return $this->parse($raw, $max);
    }

    public function normalize(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document);

        return $digits !== null && $digits !== ''
            ? $digits
            : mb_strtolower(trim($document));
    }

    /**
     * Lista para whereIn / comparación exacta (forma ingresada + normalizada).
     *
     * @param  list<string>  $documents
     * @return list<string>
     */
    public function lookupValues(array $documents): array
    {
        $values = [];

        foreach ($documents as $document) {
            $trimmed = trim((string) $document);
            if ($trimmed !== '') {
                $values[$trimmed] = $trimmed;
            }

            $normalized = $this->normalize($trimmed);
            if ($normalized !== '') {
                $values[$normalized] = $normalized;
            }
        }

        return array_values($values);
    }

    /**
     * @param  list<string>  $documents
     */
    public function matches(string $candidate, array $documents): bool
    {
        if ($documents === []) {
            return true;
        }

        $lookup = array_fill_keys(
            array_map(fn (string $doc): string => $this->normalize($doc), $documents),
            true,
        );

        $normalized = $this->normalize($candidate);

        return $normalized !== '' && isset($lookup[$normalized]);
    }

    /**
     * @param  list<string>  $documents
     */
    public function toQueryValue(array $documents): string
    {
        return implode(',', $documents);
    }
}
