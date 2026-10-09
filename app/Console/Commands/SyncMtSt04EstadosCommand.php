<?php

namespace App\Console\Commands;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Services\GestionHumana\MtSt04AuditLogService;
use App\Services\GestionHumana\MtSt04EstadoCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('mt_st_04:sync-estados {--date= : Fecha de referencia (Y-m-d) para calcular estados} {--dry-run : Mostrar cuantos cambiarian sin guardar}')]
#[Description('Sincroniza vencimientos y estados MT-ST-04 con CARGO live de Ficha')]
class SyncMtSt04EstadosCommand extends Command
{
    public function handle(
        MtSt04EstadoCalculator $calculator,
        MtSt04AuditLogService $auditLogService,
    ): int {
        $today = $this->resolveReferenceDate($calculator->timezone());
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $scanned = 0;
            $wouldUpdate = 0;

            MtSt04Registro::query()
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($calculator, $today, &$scanned, &$wouldUpdate): void {
                    $documentNumbers = $rows
                        ->pluck('document_number')
                        ->map(fn (mixed $doc): string => (string) $doc)
                        ->filter(fn (string $doc): bool => $doc !== '')
                        ->unique()
                        ->values()
                        ->all();

                    $cargos = EmployeeFichaProfile::query()
                        ->whereIn('document_number', $documentNumbers)
                        ->pluck('position_name', 'document_number');

                    /** @var MtSt04Registro $registro */
                    foreach ($rows as $registro) {
                        $scanned++;
                        $cargo = trim((string) ($cargos[$registro->document_number] ?? ''));
                        $before = [
                            'fecha_vencimiento_1' => $registro->fecha_vencimiento_1?->toDateString(),
                            'fecha_vencimiento_2' => $registro->fecha_vencimiento_2?->toDateString(),
                            'estado_1' => $registro->estado_1,
                            'estado_2' => $registro->estado_2,
                        ];
                        $calculator->applyToModel($registro, $cargo, $today);
                        $after = [
                            'fecha_vencimiento_1' => $registro->fecha_vencimiento_1?->toDateString(),
                            'fecha_vencimiento_2' => $registro->fecha_vencimiento_2?->toDateString(),
                            'estado_1' => $registro->estado_1,
                            'estado_2' => $registro->estado_2,
                        ];
                        if ($before !== $after) {
                            $wouldUpdate++;
                        }
                    }
                });

            $this->info("Dry-run: {$scanned} revisados, {$wouldUpdate} cambiarian (fecha {$today->toDateString()}).");

            return self::SUCCESS;
        }

        $result = $calculator->syncAll($today);

        $auditLogService->logEvent(
            eventType: 'sync',
            action: 'sync_estados',
            metadata: [
                'date' => $today->toDateString(),
                'scanned' => $result['scanned'],
                'updated' => $result['updated'],
            ],
        );

        $this->info(sprintf(
            'Sync estados (%s): %d revisados, %d actualizados.',
            $today->toDateString(),
            $result['scanned'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }

    private function resolveReferenceDate(string $timezone): Carbon
    {
        $dateOption = trim((string) $this->option('date'));

        if ($dateOption !== '') {
            return Carbon::parse($dateOption, $timezone)->startOfDay();
        }

        return Carbon::today($timezone)->startOfDay();
    }
}
