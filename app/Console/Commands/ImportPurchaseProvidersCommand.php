<?php

namespace App\Console\Commands;

use App\Services\PurchaseRequests\Providers\PurchaseProviderImportService;
use Illuminate\Console\Command;

class ImportPurchaseProvidersCommand extends Command
{
    protected $signature = 'purchase-providers:import
                            {path : Ruta al archivo VINE01 (.xlsx/.xls/.csv)}
                            {--dry-run : Simular sin escribir en BD}
                            {--limit= : Maximo de filas a procesar}';

    protected $description = 'Importa proveedores desde la plantilla VINE01 (solo PROVEEDOR=SI)';

    public function handle(PurchaseProviderImportService $importService): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');
        $limitInt = $limit !== null && $limit !== '' ? (int) $limit : null;

        if (! is_file($path)) {
            $this->error('No se encontro el archivo: '.$path);

            return self::FAILURE;
        }

        $this->info('Importando VINE01: '.$path
            .($dryRun ? ' (dry-run)' : '')
            .($limitInt !== null ? " (limit={$limitInt})" : ''));

        try {
            $report = $importService->import($path, $dryRun, $limitInt);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Creados', $report['created']],
                ['Actualizados', $report['updated']],
                ['Omitidos (no proveedor)', $report['skipped']],
                ['Filas vacias', $report['skipped_empty']],
                ['Procesadas', $report['processed']],
                ['Errores', $report['errors_count']],
            ]
        );

        if ($report['errors'] !== []) {
            $this->warn('Errores:');
            foreach ($report['errors'] as $error) {
                $this->line('  · '.$error);
            }

            if ($report['errors_count'] > count($report['errors'])) {
                $this->line('  · ... +'.($report['errors_count'] - count($report['errors'])).' mas');
            }
        }

        $this->info($dryRun ? 'Simulacion finalizada.' : 'Importacion finalizada.');

        return self::SUCCESS;
    }
}
