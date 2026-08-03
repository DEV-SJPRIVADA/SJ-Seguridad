<?php

namespace App\Console\Commands;

use App\Services\Comercial\CommercialServiceContractNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendCommercialServiceContractNotificationDigestCommand extends Command
{
    protected $signature = 'comercial:send-service-contract-notification-digest
                            {--date= : Fecha de referencia (Y-m-d) para pruebas}
                            {--dry-run : Listar candidatos sin enviar correo ni guardar logs}';

    protected $description = 'Envia digest diario de contratos de servicio comercial por vencer';

    public function handle(CommercialServiceContractNotificationService $service): int
    {
        $asOf = $this->resolveReferenceDate();

        if ($this->option('dry-run')) {
            $candidates = $service->collectCandidates($asOf);
            $this->info('Modo dry-run: '.count($candidates).' servicio(s) candidato(s).');

            foreach ($candidates as $row) {
                $this->line(sprintf(
                    '- %s (%s) — contrato %s — %s',
                    $row['nit'],
                    $row['client_name'],
                    $row['contract_number'] ?? '—',
                    $row['contract_end']->format('Y-m-d')
                ));
            }

            return self::SUCCESS;
        }

        $sent = $service->sendDigest($asOf, dryRun: false);

        if ($sent === 0) {
            $this->info('Sin servicios candidatos; no se envio correo.');

            return self::SUCCESS;
        }

        $this->info("Digest enviado con {$sent} servicio(s).");

        return self::SUCCESS;
    }

    private function resolveReferenceDate(): Carbon
    {
        $dateOption = trim((string) $this->option('date'));

        if ($dateOption !== '') {
            return Carbon::parse($dateOption)->startOfDay();
        }

        return now()->startOfDay();
    }
}
