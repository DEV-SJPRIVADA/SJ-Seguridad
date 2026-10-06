<?php

namespace App\Console\Commands;

use App\Services\GestionHumana\HistoricalTerminationImportService;
use Illuminate\Console\Command;

class ImportHistoricalTerminationsCommand extends Command
{
    protected $signature = 'desvinculaciones:import-historico
                            {path : Ruta al .xlsm/.xlsx (hoja NOVEDADES)}
                            {--dry-run : Simular sin escribir}
                            {--limit= : Maximo de filas de datos a procesar}
                            {--user= : ID de usuario para created_by / closed_by}';

    protected $description = 'Importa histórico de desvinculaciones (NOVEDADES) a Seguimientos + Retiros, sin cartas';

    public function handle(HistoricalTerminationImportService $importer): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');
        $limitOpt = $this->option('limit');
        $limit = $limitOpt !== null && $limitOpt !== '' ? max(1, (int) $limitOpt) : null;
        $userId = $this->option('user') !== null && $this->option('user') !== ''
            ? (int) $this->option('user')
            : null;

        if (! is_file($path)) {
            $this->error('Archivo no encontrado: '.$path);

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '').'Importando desde: '.$path);

        try {
            $stats = $importer->import($path, $dryRun, $limit, $userId);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Escaneadas (≥ mayo 2025)', $stats['scanned']],
                ['Creadas (desvincula perfil)', $stats['created']],
                ['Creadas (mantiene activo)', $stats['created_keep_activo']],
                ['Actualizadas', $stats['updated']],
                ['Sin ficha (omitidas)', $stats['skipped_no_ficha']],
                ['Errores', count($stats['errors'])],
            ]
        );

        foreach (array_slice($stats['errors'], 0, 30) as $error) {
            $this->warn('  · '.$error);
        }

        if (count($stats['errors']) > 30) {
            $this->warn('  · … y '.(count($stats['errors']) - 30).' errores más');
        }

        return self::SUCCESS;
    }
}
