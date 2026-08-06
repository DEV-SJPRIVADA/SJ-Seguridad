<?php

namespace App\Services\GestionHumana;

class EmployeeArchiveConsultationParser
{
    /**
     * @return list<string>
     */
    public function parseDocuments(string $raw): array
    {
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $documents = [];

        foreach ($parts as $part) {
            $document = trim((string) $part);
            if ($document === '') {
                continue;
            }

            $normalized = $this->normalizeDocument($document);
            if ($normalized === '') {
                continue;
            }

            $documents[$normalized] = $document;
        }

        return array_values($documents);
    }

    public function normalizeDocument(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document);

        return $digits !== '' ? $digits : mb_strtolower(trim($document));
    }
}
