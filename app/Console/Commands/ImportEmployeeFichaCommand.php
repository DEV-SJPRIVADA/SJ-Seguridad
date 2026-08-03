<?php

namespace App\Console\Commands;

use App\Services\GestionHumana\EmployeeFichaImportService;
use Illuminate\Console\Command;

class ImportEmployeeFichaCommand extends Command
{
    protected $signature = 'employee-ficha:import
                            {path : Ruta al archivo xlsx de importacion}
                            {--dry-run : Simular sin escribir}';

    protected $description = 'Importar fichas de empleados desde plantilla SJ (EMPLEADOS.xlsx)';

    public function handle(EmployeeFichaImportService $importer): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error('Archivo no encontrado: '.$path);

            return self::FAILURE;
        }

        try {
            $stats = $importer->import($path, $dryRun);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Importados', $stats['imported']],
                ['Actualizados', $stats['updated']],
                ['Omitidos', $stats['skipped']],
                ['Errores', count($stats['errors'])],
            ]
        );

        foreach (array_slice($stats['errors'], 0, 20) as $error) {
            $this->line('  · '.$error);
        }

        return self::SUCCESS;
    }
}
