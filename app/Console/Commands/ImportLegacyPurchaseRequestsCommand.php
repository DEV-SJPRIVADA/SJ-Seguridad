<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseRequestAttachmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyPurchaseRequestsCommand extends Command
{
    protected $signature = 'purchase-requests:import-legacy
                            {--dry-run : Simular sin escribir}
                            {--limit= : Maximo de registros a importar}';

    protected $description = 'Importa solicitudes de compra desde la BD legacy de gestion-compras';

    public function handle(PurchaseRequestAttachmentService $attachmentService): int
    {
        $connection = config('database.connections.legacy_gestion_compras');

        if ($connection === null || ! is_array($connection)) {
            $this->error('Configure LEGACY_GESTION_COMPRAS_DB_* en .env y database.connections.legacy_gestion_compras');

            return self::FAILURE;
        }

        if (! Schema::connection('legacy_gestion_compras')->hasTable('purchases')) {
            $this->error('No se encontro la tabla purchases en la conexion legacy.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = DB::connection('legacy_gestion_compras')
            ->table('purchases')
            ->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($query->cursor() as $legacy) {
            $user = User::query()->where('email', DB::connection('legacy_gestion_compras')->table('users')->where('id', $legacy->user_id)->value('email'))->first();
            $aprobador = User::query()->where('email', DB::connection('legacy_gestion_compras')->table('users')->where('id', $legacy->aprobador_id)->value('email'))->first();
            $areaName = DB::connection('legacy_gestion_compras')->table('areas')->where('id', $legacy->area_id)->value('nombre');
            $areaKey = $this->mapAreaKey((string) $areaName);

            if ($user === null || $aprobador === null || $areaKey === null) {
                $this->warn("Omitida purchase legacy #{$legacy->id}: usuario/aprobador/area no mapeado.");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("DRY-RUN import purchase #{$legacy->id} -> folio {$legacy->numero_solicitud}");
                $imported++;

                continue;
            }

            DB::transaction(function () use ($legacy, $user, $aprobador, $areaKey, $attachmentService): void {
                $purchaseRequest = PurchaseRequest::query()->create([
                    'numero_solicitud' => (int) $legacy->numero_solicitud,
                    'user_id' => $user->id,
                    'area_key' => $areaKey,
                    'fecha_solicitud' => $legacy->fecha_solicitud ?? $legacy->created_at,
                    'cantidad' => (int) $legacy->cantidad,
                    'solicitud_para' => $legacy->solicitud_para ?? 'Interno',
                    'urgente' => (bool) ($legacy->urgente ?? false),
                    'aprobador_id' => $aprobador->id,
                    'proyecto_nuevo' => $legacy->proyecto_nuevo,
                    'razon_social' => $legacy->razon_social,
                    'asume_cliente' => $legacy->asume_cliente,
                    'estado' => $legacy->estado,
                    'estado_compras' => $legacy->estado_compras,
                    'fecha_aprobacion' => $legacy->fecha_aprobacion,
                    'comentarios_director' => $legacy->comentarios_director,
                    'procesado_compras_at' => $legacy->procesado_compras_at,
                    'procesado_compras_por' => $legacy->procesado_compras_por
                        ? User::query()->where('email', DB::connection('legacy_gestion_compras')->table('users')->where('id', $legacy->procesado_compras_por)->value('email'))->value('id')
                        : null,
                    'comentarios_compras' => $legacy->comentarios_compras,
                    'created_at' => $legacy->created_at,
                    'updated_at' => $legacy->updated_at,
                ]);

                $items = DB::connection('legacy_gestion_compras')
                    ->table('purchase_items')
                    ->where('purchase_id', $legacy->id)
                    ->orderBy('orden')
                    ->get();

                foreach ($items as $item) {
                    PurchaseRequestItem::query()->create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'orden' => $item->orden,
                        'cantidad' => $item->cantidad,
                        'foto_path' => $item->foto_path,
                        'descripcion' => $item->descripcion,
                        'referencia' => $item->referencia,
                        'utilizacion' => $item->utilizacion,
                        'ubicacion' => $item->ubicacion,
                    ]);
                }

                $legacyPath = trim((string) ($legacy->archivo_pedido_path ?? ''));

                if ($legacyPath !== '') {
                    $attachmentService->recordMappedLegacy($purchaseRequest, $legacyPath);
                }
            });

            $imported++;
        }

        $this->info("Importacion finalizada. Importados: {$imported}. Omitidos: {$skipped}.");

        return self::SUCCESS;
    }

    private function mapAreaKey(string $areaName): ?string
    {
        $normalized = mb_strtolower(trim($areaName));

        foreach (config('access.areas', []) as $key => $label) {
            if (mb_strtolower($label) === $normalized) {
                return $key;
            }
        }

        return null;
    }
}
