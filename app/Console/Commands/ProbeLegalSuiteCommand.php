<?php

namespace App\Console\Commands;

use App\Services\Integrations\EmployeeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProbeLegalSuiteCommand extends Command
{
    protected $signature = 'integracion:probar';

    protected $description = 'Comprueba URL, TLS, credenciales y reloj contra SJ LegalSuite sin enviar empleados.';

    public function handle(EmployeeSyncService $sync): int
    {
        $this->line('Destino: '.$sync->endpoint());

        $result = $sync->probe();

        if ($result['error'] !== null) {
            $this->error('No se pudo conectar: '.$result['error']);

            return self::FAILURE;
        }

        $this->line('Respuesta HTTP '.$result['status']);
        $this->line($this->renderBody($result['body']));

        if ($result['ok']) {
            $this->info('Canal OK: TLS, token, firma y reloj validados. No se envio ningun empleado.');

            return self::SUCCESS;
        }

        $this->error($this->diagnosis((int) $result['status']));

        return self::FAILURE;
    }

    private function renderBody(mixed $body): string
    {
        if (is_array($body)) {
            return (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return (string) Str::limit((string) $body, 500);
    }

    private function diagnosis(int $status): string
    {
        return match ($status) {
            401 => 'Token incorrecto: INTEGRATION_LEGALSUITE_TOKEN no coincide con INTEGRATION_STATFLOW_KEY del receptor.',
            422 => 'Firma rechazada: el secret no coincide, el reloj esta fuera de la ventana de 300 s o un proxy modifico el cuerpo.',
            429 => 'Limite de peticiones del receptor alcanzado. Espera un minuto y reintenta.',
            503 => 'El receptor no tiene configuradas INTEGRATION_STATFLOW_KEY / INTEGRATION_STATFLOW_SECRET.',
            default => 'El receptor rechazo la peticion. Revisa el cuerpo de la respuesta.',
        };
    }
}
