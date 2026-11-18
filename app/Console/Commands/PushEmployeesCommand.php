<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PushesEmployees;
use App\Services\Integrations\EmployeeSyncService;
use Illuminate\Console\Command;

class PushEmployeesCommand extends Command
{
    use PushesEmployees;

    protected $signature = 'integracion:push-empleados
                            {--full : Envia todos los perfiles, sin filtro de fecha}
                            {--dry-run : No envia nada; reporta conteos y 5 payloads de muestra}
                            {--limit= : Maximo de perfiles a procesar (util para pruebas)}';

    protected $description = 'Envia el catalogo completo de empleados a SJ LegalSuite en lotes de 500';

    public function handle(EmployeeSyncService $sync): int
    {
        if (! $this->option('full')) {
            $this->error('Usa --full para confirmar el envio completo del catalogo.');

            return self::FAILURE;
        }

        return $this->executePush($sync, 'full');
    }
}
