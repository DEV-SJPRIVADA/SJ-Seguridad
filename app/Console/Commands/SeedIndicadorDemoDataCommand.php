<?php

namespace App\Console\Commands;

use Database\Seeders\IndicadorDemoDataSeeder;
use Illuminate\Console\Command;

class SeedIndicadorDemoDataCommand extends Command
{
    protected $signature = 'indicadores:seed-demo {--force : Ejecutar sin confirmacion}';

    protected $description = 'Carga capturas demo para los 9 indicadores FT-OP (usuario operaciones.demo@sjseguridad.test)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Se cargaran/actualizaran capturas demo para todo el anio base. ¿Continuar?')) {
            $this->info('Operacion cancelada.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => IndicadorDemoDataSeeder::class]);

        $this->info('Capturas demo cargadas.');
        $this->line('Usuario: operaciones.demo@sjseguridad.test / password');

        return self::SUCCESS;
    }
}
