<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionExportApoRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AcreditacionDashboardService
{
    public function __construct(
        private readonly AcreditacionExportApoCandidateService $candidateService,
        private readonly AcreditacionExportApoRowResolver $rowResolver,
    ) {}

    /**
     * @return array{
     *     kpis: array{
     *         en_proceso: int,
     *         acreditado: int,
     *         por_vencer: int,
     *         desacreditado: int,
     *         candidatos: int,
     *         con_novedad_blanda: int|null,
     *     },
     *     recent_runs: list<array{
     *         id: int,
     *         export_date: string,
     *         seq: int,
     *         file_name: string,
     *         user_name: string,
     *         rows_exported: int,
     *         created_at: string|null,
     *     }>,
     *     labels: array<string, string>
     * }
     */
    public function metrics(): array
    {
        $byEstado = AcreditacionAcreditado::query()
            ->selectRaw('estado, COUNT(*) as aggregate')
            ->groupBy('estado')
            ->pluck('aggregate', 'estado');

        $candidatos = $this->candidateService->candidateQuery(ordered: false)->count();
        $conNovedad = $this->countSoftNovedades($candidatos);

        return [
            'kpis' => [
                'en_proceso' => (int) ($byEstado[AcreditacionAcreditado::ESTADO_EN_PROCESO] ?? 0),
                'acreditado' => (int) ($byEstado[AcreditacionAcreditado::ESTADO_ACREDITADO] ?? 0),
                'por_vencer' => (int) ($byEstado[AcreditacionAcreditado::ESTADO_POR_VENCER] ?? 0),
                'desacreditado' => (int) ($byEstado[AcreditacionAcreditado::ESTADO_DESACREDITADO] ?? 0),
                'candidatos' => $candidatos,
                'con_novedad_blanda' => $conNovedad,
            ],
            'recent_runs' => $this->recentRuns(),
            'labels' => $this->kpiLabels(),
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     export_date: string,
     *     seq: int,
     *     file_name: string,
     *     user_name: string,
     *     rows_exported: int,
     *     created_at: string|null,
     * }>
     */
    public function recentRuns(): array
    {
        $limit = max(1, (int) config('acreditaciones.export_apo.dashboard_last_n', 20));

        /** @var Collection<int, AcreditacionExportApoRun> $runs */
        $runs = AcreditacionExportApoRun::query()
            ->with(['user:id,name'])
            ->orderByDesc('export_date')
            ->orderByDesc('seq')
            ->limit($limit)
            ->get();

        return $runs
            ->map(function (AcreditacionExportApoRun $run): array {
                $exportDate = $run->export_date instanceof Carbon
                    ? $run->export_date->format('Y-m-d')
                    : (string) $run->export_date;

                return [
                    'id' => (int) $run->id,
                    'export_date' => $exportDate,
                    'seq' => (int) $run->seq,
                    'file_name' => (string) $run->file_name,
                    'user_name' => (string) ($run->user?->name ?: '—'),
                    'rows_exported' => (int) $run->rows_exported,
                    'created_at' => $run->created_at instanceof Carbon
                        ? $run->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Cuenta candidatos con novedad blanda bajo la política dashboard.
     * Si el universo supera el tope de escaneo, retorna null (KPI omitido).
     */
    private function countSoftNovedades(int $candidatos): ?int
    {
        $maxScan = (int) config('acreditaciones.export_apo.dashboard_novedad_max_scan', 500);
        if ($candidatos === 0) {
            return 0;
        }

        if ($maxScan > 0 && $candidatos > $maxScan) {
            return null;
        }

        $policy = (string) config(
            'acreditaciones.export_apo.dashboard_novedad_policy',
            AcreditacionExportApoRowResolver::POLICY_VIGENTE_ACTUALIZAR,
        );
        $chunk = max(1, (int) config('acreditaciones.export_apo.dashboard_novedad_chunk', 50));
        $count = 0;

        $this->candidateService
            ->candidateQuery(ordered: false)
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use (&$count, $policy): void {
                foreach ($rows as $acreditado) {
                    if (! $acreditado instanceof AcreditacionAcreditado) {
                        continue;
                    }

                    $resolved = $this->rowResolver->resolve($acreditado, $policy);
                    if (! empty($resolved['soft_novedad'])) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * @return array<string, string>
     */
    private function kpiLabels(): array
    {
        /** @var array<string, string> $estadoLabels */
        $estadoLabels = config('acreditaciones.estados', []);

        return [
            'en_proceso' => $estadoLabels[AcreditacionAcreditado::ESTADO_EN_PROCESO] ?? 'EN PROCESO',
            'acreditado' => $estadoLabels[AcreditacionAcreditado::ESTADO_ACREDITADO] ?? 'ACREDITADO',
            'por_vencer' => $estadoLabels[AcreditacionAcreditado::ESTADO_POR_VENCER] ?? 'POR VENCER',
            'desacreditado' => $estadoLabels[AcreditacionAcreditado::ESTADO_DESACREDITADO] ?? 'DESACREDITADO',
            'candidatos' => 'Candidatos exportables',
            'con_novedad_blanda' => 'Con novedad blanda',
        ];
    }
}
