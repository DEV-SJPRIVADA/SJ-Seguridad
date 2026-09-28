<?php

namespace App\Services\GestionHumana;

use App\Exports\AcreditacionValidacionesConsolidatedExport;
use App\Exports\BaseExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AcreditacionValidacionesExportService
{
    public function __construct(
        private readonly AcreditacionValidacionesResultStore $resultStore,
        private readonly AcreditacionValidacionesRowFilter $rowFilter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return StreamedResponse|array{error: string, status: int}
     */
    public function exportCola(
        int $userId,
        string $fechaReporte,
        string $runToken,
        string $cola,
        array $filters = [],
    ): StreamedResponse|array {
        $payload = $this->resultStore->get($userId, $fechaReporte, $runToken);

        if ($payload === null) {
            return [
                'error' => 'Ejecute validaciones primero. La corrida expiró o el token no es válido.',
                'status' => 422,
            ];
        }

        if (! $this->resultStore->isValidCola($cola)) {
            return [
                'error' => 'Cola de validación no válida.',
                'status' => 422,
            ];
        }

        /** @var list<array<string, mixed>> $rawRows */
        $rawRows = $payload['colas'][$cola] ?? [];
        $rows = $this->rowFilter->applyFilters(collect($rawRows), $filters, $cola);
        $columns = $this->columnsForCola($cola);
        $label = $this->colaLabel($cola);
        $data = $rows->map(fn (array $row): array => $this->mapExportRow($row, $cola));

        $fileName = sprintf(
            'validaciones_%s_%s.xlsx',
            $cola,
            $fechaReporte,
        );

        return (new BaseExport(
            $data,
            $columns,
            $fileName,
            sprintf('Validaciones — %s — %s', $label, $fechaReporte),
        ))->download();
    }

    /**
     * @return StreamedResponse|array{error: string, status: int}
     */
    public function exportConsolidated(
        int $userId,
        string $fechaReporte,
        string $runToken,
    ): StreamedResponse|array {
        $payload = $this->resultStore->get($userId, $fechaReporte, $runToken);

        if ($payload === null) {
            return [
                'error' => 'Ejecute validaciones primero. La corrida expiró o el token no es válido.',
                'status' => 422,
            ];
        }

        $sheets = [];

        foreach (AcreditacionValidacionesResultStore::COLAS as $cola) {
            /** @var list<array<string, mixed>> $rows */
            $rows = $payload['colas'][$cola] ?? [];
            $sheets[] = [
                'title' => $this->sheetTitle($cola),
                'columns' => $this->columnsForCola($cola),
                'data' => collect($rows)->map(fn (array $row): array => $this->mapExportRow($row, $cola)),
            ];
        }

        return (new AcreditacionValidacionesConsolidatedExport(
            $sheets,
            sprintf('validaciones_consolidado_%s.xlsx', $fechaReporte),
            sprintf('Validaciones consolidado — %s', $fechaReporte),
        ))->download();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function columnsForCola(string $cola): array
    {
        return match ($cola) {
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'cargo', 'label' => 'Cargo Ficha'],
                ['key' => 'personal_tipo', 'label' => 'Tipo'],
            ],
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'cargo', 'label' => 'Cargo Ficha'],
                ['key' => 'cargo_apo', 'label' => 'CARGO APO'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'cargo_apo', 'label' => 'CARGO APO'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'vigencia_apo', 'label' => 'VIGEN.ACR APO'],
                ['key' => 'fecha_solicitud', 'label' => 'Fecha solicitud'],
            ],
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => [
                ['key' => 'document_number', 'label' => 'Cédula'],
                ['key' => 'full_name', 'label' => 'Nombre'],
                ['key' => 'cargo_apo', 'label' => 'CARGO APO'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'vigencia_acr', 'label' => 'Vigencia ACR'],
            ],
            default => [
                ['key' => 'document_number', 'label' => 'Cédula'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapExportRow(array $row, string $cola): array
    {
        $estado = (string) ($row['estado_label'] ?? $row['estado'] ?? '');

        return match ($cola) {
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => [
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo' => (string) ($row['cargo'] ?? ''),
                'personal_tipo' => (string) ($row['personal_tipo'] ?? ''),
            ],
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo' => (string) ($row['cargo'] ?? ''),
                'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
                'estado' => $estado,
            ],
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
                'estado' => $estado,
                'vigencia_apo' => (string) ($row['vigencia_apo'] ?? ''),
                'fecha_solicitud' => (string) ($row['fecha_solicitud'] ?? ''),
            ],
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => [
                'document_number' => (string) ($row['document_number'] ?? ''),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'cargo_apo' => (string) ($row['cargo_apo'] ?? ''),
                'estado' => $estado,
                'vigencia_acr' => (string) ($row['vigencia_acr'] ?? ''),
            ],
            default => $row,
        };
    }

    private function colaLabel(string $cola): string
    {
        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.validaciones.colas', []);

        return $labels[$cola] ?? $cola;
    }

    private function sheetTitle(string $cola): string
    {
        return match ($cola) {
            AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => 'Sin acreditacion',
            AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => 'Ausente reporte',
            AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => 'EN_PROCESO ya ACR',
            AcreditacionValidacionesResultStore::COLA_VENCIDAS => 'Vencidas',
            default => mb_substr($cola, 0, 31),
        };
    }
}
