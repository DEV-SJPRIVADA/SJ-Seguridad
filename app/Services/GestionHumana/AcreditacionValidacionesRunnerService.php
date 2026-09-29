<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use App\Models\EmployeeFichaProfile;
use Illuminate\Support\Collection;

final class AcreditacionValidacionesRunnerService
{
    public function __construct(
        private readonly AcreditacionCargoMatchNormalizer $normalizer,
        private readonly AcreditacionEstadoCalculator $estadoCalculator,
    ) {}

    /**
     * @return array{
     *     fecha_reporte: string,
     *     counts: array<string, int>,
     *     colas: array<string, list<array<string, mixed>>>
     * }
     */
    public function run(string $fechaReporte): array
    {
        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', $fechaReporte)
            ->first();

        $pairSets = $this->buildPairSets($carga);

        $sinAcreditacion = $this->buildSinAcreditacion();
        $ausenteReporte = $this->buildAusenteReporte($pairSets['all']);
        $enProcesoYaAcreditado = $this->buildEnProcesoYaAcreditado(
            $pairSets['acreditado'],
            $pairSets['acreditado_vigencia'],
        );
        $vencidas = $this->buildVencidas();

        $colas = [
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => $sinAcreditacion,
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => $ausenteReporte,
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => $enProcesoYaAcreditado,
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => $vencidas,
        ];

        return [
            'fecha_reporte' => $fechaReporte,
            'counts' => [
                AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => count($sinAcreditacion),
                AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => count($ausenteReporte),
                AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => count($enProcesoYaAcreditado),
                AcreditacionValidacionesResultStore::COLA_VENCIDAS => count($vencidas),
            ],
            'colas' => $colas,
        ];
    }

    /**
     * @return array{
     *     all: array<string, true>,
     *     acreditado: array<string, true>,
     *     acreditado_vigencia: array<string, string|null>
     * }
     */
    private function buildPairSets(?AcreditacionReporteDiarioCarga $carga): array
    {
        $all = [];
        $acreditado = [];
        $acreditadoVigencia = [];

        if ($carga === null) {
            return [
                'all' => $all,
                'acreditado' => $acreditado,
                'acreditado_vigencia' => $acreditadoVigencia,
            ];
        }

        $carga->filas()
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (AcreditacionReporteDiarioFila $fila) use (&$all, &$acreditado, &$acreditadoVigencia): void {
                $key = $this->normalizer->pairKey($fila->document_number, $fila->cargo);
                $all[$key] = true;

                if ($fila->origen === AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO) {
                    $acreditado[$key] = true;
                    if (! array_key_exists($key, $acreditadoVigencia)) {
                        $acreditadoVigencia[$key] = optional($fila->vigencia_acr)?->format('Y-m-d');
                    }
                }
            });

        return [
            'all' => $all,
            'acreditado' => $acreditado,
            'acreditado_vigencia' => $acreditadoVigencia,
        ];
    }

    /**
     * @return list<array{
     *     document_number: string,
     *     full_name: string,
     *     cargo: string,
     *     personal_tipo: string,
     *     ficha_entry_id: int|null
     * }>
     */
    private function buildSinAcreditacion(): array
    {
        /** @var Collection<int, string> $acreditadoDocs */
        $acreditadoDocs = AcreditacionAcreditado::query()
            ->pluck('document_number')
            ->map(fn (mixed $doc): string => $this->normalizer->normalizeDocument($doc))
            ->filter(fn (string $doc): bool => $doc !== '')
            ->unique()
            ->values();

        $acreditadoLookup = array_fill_keys($acreditadoDocs->all(), true);

        $rows = [];

        EmployeeFichaProfile::query()
            ->active()
            ->where('requires_acreditacion', true)
            ->with(['fichaEntry.requisition:id,operating_area_key'])
            ->lazyById(500)
            ->each(function (EmployeeFichaProfile $profile) use (&$rows, $acreditadoLookup): void {
                $doc = $this->normalizer->normalizeDocument($profile->document_number);
                if ($doc === '' || isset($acreditadoLookup[$doc])) {
                    return;
                }

                $rows[] = [
                    'document_number' => (string) $profile->document_number,
                    'full_name' => (string) ($profile->full_name ?? ''),
                    'cargo' => (string) ($profile->position_name ?? ''),
                    'personal_tipo' => $this->resolvePersonalTipo(
                        $profile->fichaEntry?->requisition?->operating_area_key
                    ),
                    'ficha_entry_id' => $profile->personal_requisition_ficha_entry_id
                        ? (int) $profile->personal_requisition_ficha_entry_id
                        : null,
                ];
            });

        usort(
            $rows,
            fn (array $a, array $b): int => strcmp(
                (string) ($a['document_number'] ?? ''),
                (string) ($b['document_number'] ?? ''),
            ),
        );

        return $rows;
    }

    /**
     * Clasificación administrativo/operativo desde el área de la requisición vinculada.
     * Operaciones → OPERATIVO; cualquier otra área con valor → ADMINISTRATIVO.
     */
    private function resolvePersonalTipo(?string $operatingAreaKey): string
    {
        $key = trim((string) $operatingAreaKey);
        if ($key === '') {
            return '';
        }

        return $key === 'operaciones' ? 'OPERATIVO' : 'ADMINISTRATIVO';
    }

    /**
     * @param  array<string, true>  $allPairs
     * @return list<array<string, mixed>>
     */
    private function buildAusenteReporte(array $allPairs): array
    {
        $rows = [];
        $fichaByDoc = $this->fichaContextByDocumentMap();

        AcreditacionAcreditado::query()
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (AcreditacionAcreditado $acreditado) use (&$rows, $allPairs, $fichaByDoc): void {
                $key = $this->normalizer->pairKey($acreditado->document_number, $acreditado->cargo_apo);
                if (isset($allPairs[$key])) {
                    return;
                }

                $row = $this->formatAcreditadoRow($acreditado, $fichaByDoc);
                if ($row !== null) {
                    $rows[] = $row;
                }
            });

        return $rows;
    }

    /**
     * @param  array<string, true>  $acreditadoPairs
     * @param  array<string, string|null>  $acreditadoVigencia
     * @return list<array<string, mixed>>
     */
    private function buildEnProcesoYaAcreditado(array $acreditadoPairs, array $acreditadoVigencia): array
    {
        $rows = [];
        $fichaByDoc = $this->fichaContextByDocumentMap();

        AcreditacionAcreditado::query()
            ->where('estado', AcreditacionAcreditado::ESTADO_EN_PROCESO)
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (AcreditacionAcreditado $acreditado) use (&$rows, $acreditadoPairs, $acreditadoVigencia, $fichaByDoc): void {
                $key = $this->normalizer->pairKey($acreditado->document_number, $acreditado->cargo_apo);
                if (! isset($acreditadoPairs[$key])) {
                    return;
                }

                $row = $this->formatAcreditadoRow($acreditado, $fichaByDoc);
                if ($row === null) {
                    return;
                }
                $row['vigencia_apo'] = $acreditadoVigencia[$key] ?? null;
                $rows[] = $row;
            });

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildVencidas(): array
    {
        $rows = [];
        $fichaByDoc = $this->fichaContextByDocumentMap();

        AcreditacionAcreditado::query()
            ->whereIn('estado', [
                AcreditacionAcreditado::ESTADO_DESACREDITADO,
                AcreditacionAcreditado::ESTADO_POR_VENCER,
            ])
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (AcreditacionAcreditado $acreditado) use (&$rows, $fichaByDoc): void {
                $row = $this->formatAcreditadoRow($acreditado, $fichaByDoc);
                if ($row !== null) {
                    $rows[] = $row;
                }
            });

        return $rows;
    }

    /**
     * @return array<string, array{ficha_entry_id: int|null, cargo: string, requires_acreditacion: bool}>
     */
    private function fichaContextByDocumentMap(): array
    {
        $map = [];

        EmployeeFichaProfile::query()
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (EmployeeFichaProfile $profile) use (&$map): void {
                $doc = (string) $profile->document_number;
                if ($doc === '') {
                    return;
                }
                $map[$doc] = [
                    'ficha_entry_id' => $profile->personal_requisition_ficha_entry_id
                        ? (int) $profile->personal_requisition_ficha_entry_id
                        : null,
                    'cargo' => trim((string) ($profile->position_name ?? '')),
                    'requires_acreditacion' => (bool) ($profile->requires_acreditacion ?? true),
                ];
            });

        return $map;
    }

    /**
     * @param  array<string, array{ficha_entry_id: int|null, cargo: string, requires_acreditacion?: bool}>  $fichaByDoc
     * @return array<string, mixed>|null
     */
    private function formatAcreditadoRow(AcreditacionAcreditado $acreditado, array $fichaByDoc = []): ?array
    {
        $doc = (string) $acreditado->document_number;
        $ficha = $fichaByDoc[$doc] ?? ['ficha_entry_id' => null, 'cargo' => '', 'requires_acreditacion' => true];

        if (! ($ficha['requires_acreditacion'] ?? true)) {
            return null;
        }

        return [
            'acreditado_id' => $acreditado->id,
            'document_number' => $doc,
            'full_name' => (string) $acreditado->full_name,
            'cargo' => (string) ($ficha['cargo'] ?? ''),
            'cargo_apo' => (string) $acreditado->cargo_apo,
            'estado' => (string) $acreditado->estado,
            'estado_label' => $this->estadoCalculator->estadoLabel((string) $acreditado->estado),
            'vigencia_acr' => optional($acreditado->vigencia_acr)?->format('Y-m-d'),
            'fecha_solicitud' => optional($acreditado->fecha_solicitud)?->format('Y-m-d'),
            'renovacion' => (string) ($acreditado->renovacion ?? ''),
            'observaciones' => (string) ($acreditado->observaciones ?? ''),
            'ficha_entry_id' => $ficha['ficha_entry_id'] ?? null,
        ];
    }
}
