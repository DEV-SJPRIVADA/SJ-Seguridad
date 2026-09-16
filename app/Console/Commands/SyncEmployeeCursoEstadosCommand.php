<?php

namespace App\Console\Commands;

use App\Models\EmployeeCurso;
use App\Services\GestionHumana\EmployeeCursoEstadoSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncEmployeeCursoEstadosCommand extends Command
{
    protected $signature = 'cursos:sync-estados
                            {--backfill : Fuerza VIGENTE → ACTUALIZADO (ajuste inicial de datos)}
                            {--date= : Fecha de referencia (Y-m-d) para calcular vigencia}
                            {--dry-run : Mostrar cuantos cambiarian sin guardar}';

    protected $description = 'Sincroniza ESTADO de cursos segun vigencia (PENDIENTE / ACTUALIZADO; conserva SOLICITADO)';

    public function handle(EmployeeCursoEstadoSyncService $syncService): int
    {
        $today = $this->resolveReferenceDate();
        $backfill = (bool) $this->option('backfill');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $scanned = 0;
            $wouldUpdate = 0;

            EmployeeCurso::query()
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($syncService, $backfill, $today, &$scanned, &$wouldUpdate): void {
                    foreach ($rows as $curso) {
                        $scanned++;
                        $before = $curso->estado;
                        $syncService->applyToModel(
                            $curso,
                            forceVigente: $backfill,
                            preserveSolicitado: true,
                            today: $today,
                        );
                        if ($curso->estado !== $before) {
                            $wouldUpdate++;
                        }
                    }
                });

            $this->info("Dry-run: {$scanned} revisados, {$wouldUpdate} cambiarian (fecha {$today->toDateString()}).");

            return self::SUCCESS;
        }

        $result = $syncService->syncAll(backfill: $backfill, today: $today);

        $mode = $backfill ? 'backfill' : 'diario';
        $this->info(sprintf(
            'Sync estados (%s, %s): %d revisados, %d actualizados.',
            $mode,
            $today->toDateString(),
            $result['scanned'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }

    private function resolveReferenceDate(): Carbon
    {
        $dateOption = trim((string) $this->option('date'));

        if ($dateOption !== '') {
            return Carbon::parse($dateOption)->startOfDay();
        }

        return Carbon::today()->startOfDay();
    }
}
