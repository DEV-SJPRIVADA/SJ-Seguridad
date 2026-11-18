<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PushesEmployees;
use App\Services\Integrations\EmployeeSyncService;
use Illuminate\Console\Command;

class PushEmployeeChangesCommand extends Command
{
    use PushesEmployees;

    protected $signature = 'integracion:push-cambios
                            {--dry-run : No envia nada; reporta conteos y 5 payloads de muestra}
                            {--limit= : Maximo de perfiles a procesar (util para pruebas)}';

    protected $description = 'Empuja a SJ LegalSuite los empleados modificados desde el ultimo watermark';

    public function handle(EmployeeSyncService $sync): int
    {
        return $this->executePush($sync, 'incremental');
    }
}
