<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeFichaProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Prefill de filas Export Apo Manual (base: Acreditados; sin bloquear por Valida/novedades).
 *
 * - Cargo: número de cargo de acreditación (Gestionar cargos). Con acreditado sale de su `cargo_apo`;
 *   sin acreditado, del cargo del perfil (`cargo_manager`).
 * - Curso: el de fecha de expedición más reciente cuyo tipo tenga ese número en `cargo_acredit`,
 *   en cualquier vigencia (Manual no aplica la política de vigencia).
 * - Avisos informativos: cargo fuera del catálogo, cargos Acreditados ↔ perfil distintos, sin acreditado.
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
     *     row: array<string, string>,
     *     avisos: string
     * }
     */
    public function prefill(string $documentNumber): array
    {
        $document = $this->normalizeDocument($documentNumber);
        $empty = $this->emptyRow($document);

        if ($document === '') {
            return ['found' => false, 'source' => null, 'row' => $empty, 'avisos' => ''];
        }

        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $document)
            ->orderByDesc('id')
            ->first();
        $perfilNumber = $profile !== null ? $this->rowResolver->cargoNumberByManager($profile->position_name) : null;

        /** @var Collection<int, AcreditacionAcreditado> $acreditados */
        $acreditados = AcreditacionAcreditado::query()
            ->where('document_number', $document)
            ->orderByDesc('id')
            ->get();

        if ($acreditados->isNotEmpty()) {
            $acreditado = $this->pickAcreditadoForCargo($acreditados, $perfilNumber);
            $resolved = $this->rowResolver->resolve($acreditado, AcreditacionExportApoRowResolver::POLICY_CUALQUIERA);

            return [
                'found' => true,
                'source' => 'acreditado',
                'row' => $this->exportableFields($resolved, $document),
                'avisos' => (string) ($resolved['motivo'] ?? ''),
            ];
        }

        if ($profile === null) {
            return ['found' => false, 'source' => null, 'row' => $empty, 'avisos' => ''];
        }

        $codes = [AcreditacionExportApoRowResolver::NOVEDAD_SIN_ACREDITADO];
        $curso = ['codigo_curso' => '', 'nit_escuela' => '', 'nro' => ''];

        if ($perfilNumber === null) {
            $codes[] = AcreditacionExportApoRowResolver::NOVEDAD_CARGO_PERFIL_NO_CATALOGO;
        } else {
            $curso = $this->rowResolver->resolveCursoByCargoNumber(
                $document,
                $perfilNumber,
                AcreditacionExportApoRowResolver::POLICY_CUALQUIERA,
            );
            $codes = [...$codes, ...$curso['codes']];
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
                'cargo' => (string) ($perfilNumber ?? ''),
                'fecha_vinculacion' => AcreditacionExportApoRowResolver::defaultFechaVinculacion('Y-m-d'),
                'codigo_curso' => (string) $curso['codigo_curso'],
                'nit_escuela' => (string) $curso['nit_escuela'],
                'nro' => (string) $curso['nro'],
            ],
            'avisos' => $this->rowResolver->novedadesLabel($codes),
        ];
    }

    /**
     * @param  list<string>  $documents
     * @return list<array{document_number: string, found: bool, source: string|null, row: array<string, string>, avisos: string}>
     */
    public function prefillMany(array $documents): array
    {
        $seen = [];
        $out = [];

        foreach ($documents as $raw) {
            $document = $this->normalizeDocument((string) $raw);
            if ($document === '' || isset($seen[$document])) {
                continue;
            }
            $seen[$document] = true;
            $result = $this->prefill($document);
            $out[] = [
                'document_number' => $document,
                'found' => $result['found'],
                'source' => $result['source'],
                'row' => $result['row'],
                'avisos' => $result['avisos'],
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
     * Varios registros en Acreditados: el más reciente cuyo cargo APO tenga el mismo número de cargo
     * de acreditación que el perfil (p. ej. GUARDA → 1 elige VIGILANTE y no ESCOLTA); si ninguno, el más reciente.
     *
     * @param  Collection<int, AcreditacionAcreditado>  $acreditados
     */
    private function pickAcreditadoForCargo(Collection $acreditados, ?string $perfilNumber): AcreditacionAcreditado
    {
        if ($perfilNumber !== null) {
            $match = $acreditados->first(
                fn (AcreditacionAcreditado $row): bool => $this->rowResolver->cargoNumberByApo($row->cargo_apo) === $perfilNumber
            );
            if ($match instanceof AcreditacionAcreditado) {
                return $match;
            }
        }

        /** @var AcreditacionAcreditado $latest */
        $latest = $acreditados->first();

        return $latest;
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
