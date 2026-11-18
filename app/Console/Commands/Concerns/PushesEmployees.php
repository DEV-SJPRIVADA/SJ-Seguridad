<?php

namespace App\Console\Commands\Concerns;

use App\Services\Integrations\EmployeeSyncService;

trait PushesEmployees
{
    protected function executePush(EmployeeSyncService $sync, string $mode): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limitOption = $this->option('limit');
        $limit = $limitOption === null || $limitOption === '' ? null : max(0, (int) $limitOption);

        if (! $dryRun) {
            if (! $sync->enabled()) {
                $this->error('Integracion deshabilitada. Define INTEGRATION_LEGALSUITE_ENABLED=true para enviar.');

                return self::FAILURE;
            }

            $missing = $sync->missingConfiguration();

            if ($missing !== []) {
                $this->error('Faltan variables de entorno: '.implode(', ', $missing));

                return self::FAILURE;
            }
        }

        $watermark = $mode === 'incremental'
            ? ($sync->state()->last_watermark ?? now()->subDay())
            : now()->subDay();

        $this->line(sprintf(
            'Modo %s · watermark %s · lote %d%s',
            $mode,
            $watermark->toDateTimeString(),
            $sync->batchSize(),
            $limit !== null ? " · limite {$limit}" : ''
        ));

        $result = $sync->run($mode, $watermark, $limit, $dryRun);

        if ($result['status'] === 'dry-run') {
            $this->info('DRY-RUN: no se envio ninguna peticion HTTP.');
            $this->table(['Metrica', 'Valor'], [
                ['Registros a enviar', $result['total']],
                ['Lotes de '.$sync->batchSize(), $result['batches']],
                ['Sin position_code', $result['stats']['sin_position_code'] ?? 0],
                ['Sin email', $result['stats']['sin_email'] ?? 0],
                ['document_type desconocido', $result['stats']['document_type_desconocido'] ?? 0],
            ]);

            foreach ($result['samples'] as $index => $sample) {
                $this->line('--- Muestra '.($index + 1).' ---');
                $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
            }

            return self::SUCCESS;
        }

        if ($result['status'] !== 'ok') {
            $this->error('Sincronizacion fallida: '.($result['message'] ?? 'error desconocido'));
            $this->line(sprintf('Enviados antes del fallo: %d · fallidos: %d · lotes: %d', $result['sent'], $result['failed'], $result['batches']));

            return self::FAILURE;
        }

        $watermarkAfter = $result['max_updated_at'] ?? 'sin cambios';

        $this->info(sprintf(
            'Sincronizacion OK: %d enviados, %d fallidos, %d lotes. Watermark: %s',
            $result['sent'],
            $result['failed'],
            $result['batches'],
            is_string($watermarkAfter) ? $watermarkAfter : $watermarkAfter->toDateTimeString()
        ));

        if ($result['sent'] === 0) {
            $this->line('No habia cambios pendientes.');
        }

        return self::SUCCESS;
    }
}
