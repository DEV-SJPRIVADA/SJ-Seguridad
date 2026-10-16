<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
use Illuminate\Support\Carbon;

/**
 * Prefill de filas Export Apo Manual: arma desde ficha/perfil (y acreditado si hay)
 * sin bloquear por Valida/novedades. Si no hay datos, devuelve fila vacía editable.
 */
final class AcreditacionExportApoManualPrefillService
{
    public function __construct(
        private readonly AcreditacionExportApoRowResolver $rowResolver,
    ) {}

    /**
     * @return array{
     *     found: bool,
     *     source: string|null,
     *     row: array<string, string>
     * }
     */
    public function prefill(string $documentNumber, ?string $vigenciaPolicy = null): array
    {
        $document = $this->normalizeDocument($documentNumber);
        $empty = $this->emptyRow($document);

        if ($document === '') {
            return [
                'found' => false,
                'source' => null,
                'row' => $empty,
            ];
        }

        $policy = $this->rowResolver->normalizePolicy(
            $vigenciaPolicy ?: AcreditacionExportApoRowResolver::POLICY_VIGENTE
        );

        $acreditado = AcreditacionAcreditado::query()
            ->where('document_number', $document)
            ->orderByDesc('id')
            ->first();

        if ($acreditado !== null) {
            $resolved = $this->rowResolver->resolve($acreditado, $policy);

            return [
                'found' => true,
                'source' => 'acreditado',
                'row' => $this->exportableFields($resolved, $document),
            ];
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $document)
            ->orderByDesc('id')
            ->first();

        if ($profile === null) {
            return [
                'found' => false,
                'source' => null,
                'row' => $empty,
            ];
        }

        return [
            'found' => true,
            'source' => 'ficha',
            'row' => [
                'document_number' => $document,
                'nombre1' => trim((string) ($profile->first_name ?? '')),
                'nombre2' => trim((string) ($profile->second_name ?? '')),
                'apellido1' => trim((string) ($profile->first_surname ?? '')),
                'apellido2' => trim((string) ($profile->second_surname ?? '')),
                'fecha_nacimiento' => $this->formatDate($profile->birth_date),
                'genero' => $this->mapGenero((string) ($profile->sex ?? '')),
                'cargo' => trim((string) ($profile->position_name ?? $profile->position_code ?? '')),
                'fecha_vinculacion' => AcreditacionExportApoRowResolver::defaultFechaVinculacion('Y-m-d'),
                'codigo_curso' => '',
                'nit_escuela' => '',
                'nro' => '',
            ],
        ];
    }

    /**
     * @param  list<string>  $documents
     * @return list<array{document_number: string, found: bool, source: string|null, row: array<string, string>}>
     */
    public function prefillMany(array $documents, ?string $vigenciaPolicy = null): array
    {
        $seen = [];
        $out = [];

        foreach ($documents as $raw) {
            $document = $this->normalizeDocument((string) $raw);
            if ($document === '' || isset($seen[$document])) {
                continue;
            }
            $seen[$document] = true;
            $result = $this->prefill($document, $vigenciaPolicy);
            $out[] = [
                'document_number' => $document,
                'found' => $result['found'],
                'source' => $result['source'],
                'row' => $result['row'],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function emptyRow(string $documentNumber = ''): array
    {
        return [
            'document_number' => $documentNumber,
            'nombre1' => '',
            'nombre2' => '',
            'apellido1' => '',
            'apellido2' => '',
            'fecha_nacimiento' => '',
            'genero' => '',
            'cargo' => '',
            'fecha_vinculacion' => AcreditacionExportApoRowResolver::defaultFechaVinculacion('Y-m-d'),
            'codigo_curso' => '',
            'nit_escuela' => '',
            'nro' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, string>
     */
    private function exportableFields(array $resolved, string $document): array
    {
        return [
            'document_number' => $document !== '' ? $document : (string) ($resolved['document_number'] ?? ''),
            'nombre1' => (string) ($resolved['nombre1'] ?? ''),
            'nombre2' => (string) ($resolved['nombre2'] ?? ''),
            'apellido1' => (string) ($resolved['apellido1'] ?? ''),
            'apellido2' => (string) ($resolved['apellido2'] ?? ''),
            'fecha_nacimiento' => $this->formatDate($resolved['fecha_nacimiento'] ?? ''),
            'genero' => (string) ($resolved['genero'] ?? ''),
            'cargo' => (string) ($resolved['cargo'] ?? ''),
            // Fecha vinc. Manual: siempre ayer (mismo criterio que Automática).
            'fecha_vinculacion' => AcreditacionExportApoRowResolver::defaultFechaVinculacion('Y-m-d'),
            'codigo_curso' => (string) ($resolved['codigo_curso'] ?? ''),
            'nit_escuela' => (string) ($resolved['nit_escuela'] ?? ''),
            'nro' => (string) ($resolved['nro'] ?? ''),
        ];
    }

    private function normalizeDocument(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : trim($value);
    }

    /**
     * Fechas para input type="date" (YYYY-MM-DD). Acepta también dd/mm/yyyy del resolver.
     */
    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m) === 1) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function mapGenero(string $sex): string
    {
        $upper = mb_strtoupper(trim($sex));

        return match (true) {
            $upper === '' => '',
            in_array($upper, ['M', '1', 'MASCULINO', 'MASC'], true) => '1',
            in_array($upper, ['F', '2', 'FEMENINO', 'FEM'], true) => '2',
            str_starts_with($upper, 'M') => '1',
            str_starts_with($upper, 'F') => '2',
            default => $sex,
        };
    }
}
