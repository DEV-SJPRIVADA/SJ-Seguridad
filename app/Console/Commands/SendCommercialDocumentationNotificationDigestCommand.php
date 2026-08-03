<?php

namespace App\Console\Commands;

use App\Services\Comercial\CommercialDocumentationNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendCommercialDocumentationNotificationDigestCommand extends Command
{
    protected $signature = 'comercial:send-documentation-notification-digest
                            {--date= : Fecha de referencia (Y-m-d) para pruebas}
                            {--dry-run : Listar candidatos sin enviar correo ni guardar logs}';

    protected $description = 'Envia digest diario de documentacion comercial por vencer o vencida';

    public function handle(CommercialDocumentationNotificationService $service): int
    {
        $asOf = $this->resolveReferenceDate();

        if ($this->option('dry-run')) {
            $candidates = $service->collectCandidates($asOf);
            $this->info('Modo dry-run: '.count($candidates).' cliente(s) candidato(s).');

            foreach ($candidates as $row) {
                $this->line(sprintf(
                    '- %s (%s) — %s — %s',
                    $row['nit'],
                    $row['name'],
                    $row['status_label'],
                    $row['documentation_expires_on']->format('Y-m-d')
                ));
            }

            return self::SUCCESS;
        }

        $sent = $service->sendDigest($asOf, dryRun: false);

        if ($sent === 0) {
            $this->info('Sin clientes candidatos; no se envio correo.');

            return self::SUCCESS;
        }

        $this->info("Digest enviado con {$sent} cliente(s).");

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
