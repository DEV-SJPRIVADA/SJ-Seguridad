<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use Illuminate\Support\Collection;

final class AcreditacionExportApoPreviewService
{
    public function __construct(
        private readonly AcreditacionExportApoCandidateService $candidateService,
        private readonly AcreditacionExportApoRowResolver $rowResolver,
    ) {}

    /**
     * Preview de todo el universo candidato (botón Validar).
     *
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{
     *         selected: int,
     *         found: int,
     *         validas: int,
     *         novedades_blandas: int,
     *         bloqueadas: int,
     *         missing_ids: list<int>,
     *     },
     * }
     */
    public function previewAll(string $vigenciaPolicy): array
    {
        return $this->buildPreview(
            $this->candidateService->allCandidates(),
            $vigenciaPolicy,
            requestedIds: [],
            forceOutsideUniverse: false,
        );
    }

    /**
     * Preview de IDs seleccionados (incluye fuera del universo → bloqueo duro).
     *
     * @param  list<int>  $ids
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{
     *         selected: int,
     *         found: int,
     *         validas: int,
     *         novedades_blandas: int,
     *         bloqueadas: int,
     *         missing_ids: list<int>,
     *     },
     * }
     */
    public function preview(array $ids, string $vigenciaPolicy): array
    {
        $requestedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $this->buildPreview(
            $this->candidateService->findByIds($requestedIds),
            $vigenciaPolicy,
            $requestedIds,
            forceOutsideUniverse: true,
        );
    }

    /**
     * @param  Collection<int, AcreditacionAcreditado>  $acreditados
     * @param  list<int>  $requestedIds
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{
     *         selected: int,
     *         found: int,
     *         validas: int,
     *         novedades_blandas: int,
     *         bloqueadas: int,
     *         missing_ids: list<int>,
     *     },
     * }
     */
    private function buildPreview(
        Collection $acreditados,
        string $vigenciaPolicy,
        array $requestedIds,
        bool $forceOutsideUniverse,
    ): array {
        $policy = $this->rowResolver->normalizePolicy($vigenciaPolicy);
        $foundIds = $acreditados->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $missingIds = $requestedIds === []
            ? []
            : array_values(array_diff($requestedIds, $foundIds));

        $candidateIdSet = [];
        if ($forceOutsideUniverse && $requestedIds !== []) {
            $candidateIdSet = array_fill_keys(
                $this->candidateService->findCandidatesByIds($requestedIds)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
                true,
            );
        }

        $rows = $acreditados
            ->map(function (AcreditacionAcreditado $row) use ($policy, $forceOutsideUniverse, $candidateIdSet): array {
                $resolved = $this->rowResolver->resolve($row, $policy);

                if ($forceOutsideUniverse && ! isset($candidateIdSet[(int) $row->id])) {
                    $resolved = $this->rowResolver->markFueraUniverso($resolved);
                }

                return $resolved;
            })
            ->values()
            ->all();

        foreach ($missingIds as $missingId) {
            $rows[] = $this->rowResolver->missingRecordStub((int) $missingId);
        }

        $validas = 0;
        $blandas = 0;
        $bloqueadas = 0;

        foreach ($rows as $row) {
            if ($row['hard_block'] === true) {
                $bloqueadas++;
            } elseif ($row['soft_novedad'] === true) {
                $blandas++;
            } else {
                $validas++;
            }
        }

        return [
            'rows' => $rows,
            'summary' => [
                'selected' => $requestedIds === [] ? count($foundIds) : count($requestedIds),
                'found' => count($foundIds),
                'validas' => $validas,
                'novedades_blandas' => $blandas,
                'bloqueadas' => $bloqueadas,
                'missing_ids' => $missingIds,
            ],
        ];
    }
}
