<?php

namespace App\Console\Commands;

use App\Models\AcreditacionAcreditado;
use App\Services\GestionHumana\AcreditacionEstadoCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('acreditaciones:sync-estados {--date= : Fecha de referencia (Y-m-d) para calcular estados} {--dry-run : Mostrar cuantos cambiarian sin guardar}')]
#[Description('Sincroniza ESTADO de acreditados segun vigencia y fecha de solicitud')]
class SyncAcreditacionEstadosCommand extends Command
{
    public function handle(AcreditacionEstadoCalculator $calculator): int
    {
        $today = $this->resolveReferenceDate();
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $scanned = 0;
            $wouldUpdate = 0;

            AcreditacionAcreditado::query()
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($calculator, $today, &$scanned, &$wouldUpdate): void {
                    foreach ($rows as $acreditado) {
                        $scanned++;
                        $before = $acreditado->estado;
                        $calculator->applyToModel($acreditado, $today);
                        if ($acreditado->estado !== $before) {
                            $wouldUpdate++;
                        }
                    }
                });

            $this->info("Dry-run: {$scanned} revisados, {$wouldUpdate} cambiarian (fecha {$today->toDateString()}).");

            return self::SUCCESS;
        }

        $result = $calculator->syncAll($today);

        $this->info(sprintf(
            'Sync estados (%s): %d revisados, %d actualizados.',
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

        return Carbon::today(config('app.timezone', 'America/Bogota'))->startOfDay();
    }
}
