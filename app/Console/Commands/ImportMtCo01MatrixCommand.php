<?php

namespace App\Console\Commands;

use App\Services\Comercial\MtCo01Importer;
use Illuminate\Console\Command;

class ImportMtCo01MatrixCommand extends Command
{
    protected $signature = 'comercial:import-mt-co-01
                            {path : Ruta absoluta al archivo .xlsx MT-CO-01}
                            {--fresh : Vaciar clientes/servicios comerciales antes de importar}';

    protected $description = 'Importa la matriz MT-CO-01 (Excel) a commercial_clients / commercial_services';

    public function handle(MtCo01Importer $importer): int
    {
        $path = $this->argument('path');
        $fresh = (bool) $this->option('fresh');

        if (! is_file($path)) {
            $this->error('No se encontro el archivo: '.$path);
            $this->line('El Excel MT-CO-01 no se versiona en el repositorio; use una copia local o de red.');

            return self::FAILURE;
        }

        $this->info('Importando: '.$path.($fresh ? ' (fresh)' : ''));

        try {
            $stats = $importer->import($path, $fresh);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Clientes nuevos', $stats['clients']],
                ['Servicios nuevos', $stats['services']],
                ['Filas omitidas', $stats['skipped']],
            ]
        );

        foreach ($stats['sheets'] as $sheet => $count) {
            $this->line("  - {$sheet}: {$count} servicios nuevos");
        }

        if ($stats['errors'] !== []) {
            $this->warn('Avisos/errores:');
            foreach (array_slice($stats['errors'], 0, 30) as $error) {
                $this->line('  · '.$error);
            }
            if (count($stats['errors']) > 30) {
                $this->line('  · ... +'.(count($stats['errors']) - 30).' mas');
            }
        }

        $this->info('Importacion finalizada.');

        return self::SUCCESS;
    }
}
