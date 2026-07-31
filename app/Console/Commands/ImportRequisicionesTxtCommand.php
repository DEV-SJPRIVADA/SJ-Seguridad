<?php

namespace App\Console\Commands;

use App\Services\Requisitions\RequisicionesTxtImporter;
use Illuminate\Console\Command;

class ImportRequisicionesTxtCommand extends Command
{
    protected $signature = 'requisitions:import-txt
                            {path : Ruta al archivo Requisiciones.txt (export pipe-delimited)}
                            {--dry-run : Simular sin escribir en BD}
                            {--limit= : Maximo de filas a procesar}
                            {--fresh : Eliminar requisiciones REQ-IMP-* y fichas antes de importar}';

    protected $description = 'Importa requisiciones de personal desde archivo legacy Requisiciones.txt';

    public function handle(RequisicionesTxtImporter $importer): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');
        $limit = $this->option('limit');
        $limitInt = $limit !== null && $limit !== '' ? (int) $limit : null;

        if (! is_file($path)) {
            $this->error('No se encontro el archivo: '.$path);

            return self::FAILURE;
        }

        $this->info('Importando: '.$path
            .($dryRun ? ' (dry-run)' : '')
            .($fresh ? ' (fresh)' : '')
            .($limitInt !== null ? " (limit={$limitInt})" : ''));

        try {
            $stats = $importer->import($path, $dryRun, $limitInt, $fresh);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Importadas', $stats['imported']],
                ['Omitidas', $stats['skipped']],
                ['Entradas ficha', $stats['ficha_entries']],
                ['Errores', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            $this->warn('Errores:');
            foreach (array_slice($stats['errors'], 0, 20) as $error) {
                $this->line('  · '.$error);
            }
            if (count($stats['errors']) > 20) {
                $this->line('  · ... +'.(count($stats['errors']) - 20).' mas');
            }
        }

        $this->info($dryRun ? 'Simulacion finalizada.' : 'Importacion finalizada.');

        return self::SUCCESS;
    }
}
