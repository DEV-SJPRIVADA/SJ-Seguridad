<?php

namespace App\Console\Commands;

use App\Services\GestionHumana\PayrollCatalogSeeder;
use Illuminate\Console\Command;

class SeedEmployeeFichaCatalogsCommand extends Command
{
    protected $signature = 'employee-ficha:seed-catalogs
                            {--from=docs/Contratacion : Directorio con ACTIVOS y EMPLEADOS.xlsx}
                            {--dry-run : Simular sin escribir}';

    protected $description = 'Sembrar catalogos nómina desde ACTIVOS/EMPLEADOS (upsert, no destructivo)';

    public function handle(PayrollCatalogSeeder $seeder): int
    {
        $from = base_path($this->option('from'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_dir($from)) {
            $this->error('Directorio no encontrado: '.$from);

            return self::FAILURE;
        }

        $this->info('Sembrando catalogos desde: '.$from.($dryRun ? ' (dry-run)' : ''));

        $stats = $seeder->seedFromDirectory($from, $dryRun);

        if ($stats === []) {
            $this->warn('No se encontraron archivos ACTIVOS o EMPLEADOS.xlsx.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($stats as $type => $count) {
            $rows[] = [$type, $count];
        }

        $this->table(['Catalogo', 'Registros procesados'], $rows);
        $this->info('Seed finalizado.');

        return self::SUCCESS;
    }
}
